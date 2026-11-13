<?php

declare(strict_types=1);

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

function json(Response $response, mixed $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

$app->get('/health', function (Request $request, Response $response) {
    return json($response, ['status' => 'ok']);
});

$app->post('/scores', function (Request $request, Response $response) {
    $data = (array) $request->getParsedBody();
    $player = trim((string) ($data['player'] ?? ''));
    $points = $data['points'] ?? null;
    $hits = $data['hits'] ?? 0;

    if (
        $player === '' || mb_strlen($player) > 30
        || !is_int($points) || $points < 0
        || !is_int($hits) || $hits < 0
    ) {
        return json($response, ['error' => 'Datos no válidos'], 400);
    }

    $db = Database::connect();
    $stmt = $db->prepare('INSERT INTO scores (player, points, hits) VALUES (:player, :points, :hits)');
    $stmt->execute(['player' => $player, 'points' => $points, 'hits' => $hits]);

    return json($response, [
        'id' => (int) $db->lastInsertId(),
        'player' => $player,
        'points' => $points,
        'hits' => $hits,
    ], 201);
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

$app->run();