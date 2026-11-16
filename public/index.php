<?php

declare(strict_types=1);

use App\Database;
use App\Leaderboard;
use App\RedisClient;
use App\ScoreInput;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(getenv('APP_ENV') !== 'production', true, true);

$app->add(function (Request $request, Handler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', getenv('CORS_ORIGIN') ?: '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

function json(Response $response, mixed $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

$app->get('/health', function (Request $request, Response $response) {
    return json($response, ['status' => 'ok']);
});

$app->post('/scores', function (Request $request, Response $response) {
    $input = ScoreInput::fromArray((array) $request->getParsedBody());

    if ($input === null) {
        return json($response, ['error' => 'Datos no válidos'], 400);
    }

    $db = Database::connect();
    $stmt = $db->prepare('INSERT INTO scores (player, points, hits) VALUES (:player, :points, :hits)');
    $stmt->execute(['player' => $input->player, 'points' => $input->points, 'hits' => $input->hits]);

    (new Leaderboard(RedisClient::connect()))->submit($input->player, $input->points);

    return json($response, [
        'id' => (int) $db->lastInsertId(),
        'player' => $input->player,
        'points' => $input->points,
        'hits' => $input->hits,
    ], 201);
});

$app->get('/leaderboard', function (Request $request, Response $response) {
    $leaderboard = new Leaderboard(RedisClient::connect());
    return json($response, $leaderboard->top());
});

$app->get('/players/{name}', function (Request $request, Response $response, array $args) {
    $stmt = Database::connect()->prepare(
        'SELECT points, hits, created_at FROM scores WHERE player = :player ORDER BY created_at DESC, id DESC LIMIT 10'
    );
    $stmt->execute(['player' => $args['name']]);
    $scores = $stmt->fetchAll();

    if ($scores === []) {
        return json($response, ['error' => 'Jugador no encontrado'], 404);
    }

    return json($response, [
        'player' => $args['name'],
        'best' => max(array_column($scores, 'points')),
        'scores' => $scores,
    ]);
});

$app->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/{routes:.+}', function (Request $request) {
    throw new HttpNotFoundException($request);
});

$app->run();