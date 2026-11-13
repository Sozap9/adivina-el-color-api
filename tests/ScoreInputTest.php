<?php

declare(strict_types=1);

namespace Tests;

use App\ScoreInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScoreInputTest extends TestCase
{
    public function testValidDataCreatesInput(): void
    {
        $input = ScoreInput::fromArray(['player' => '  Diego  ', 'points' => 120, 'hits' => 8]);

        $this->assertNotNull($input);
        $this->assertSame('Diego', $input->player);
        $this->assertSame(120, $input->points);
        $this->assertSame(8, $input->hits);
    }

    public function testHitsDefaultToZero(): void
    {
        $input = ScoreInput::fromArray(['player' => 'Diego', 'points' => 50]);

        $this->assertNotNull($input);
        $this->assertSame(0, $input->hits);
    }

    public function testNameWithThirtyAccentedCharactersIsValid(): void
    {
        $this->assertNotNull(ScoreInput::fromArray(['player' => str_repeat('ñ', 30), 'points' => 10]));
    }

    #[DataProvider('invalidData')]
    public function testInvalidDataIsRejected(array $data): void
    {
        $this->assertNull(ScoreInput::fromArray($data));
    }

    public static function invalidData(): array
    {
        return [
            'sin nombre' => [['points' => 10]],
            'nombre vacío' => [['player' => '   ', 'points' => 10]],
            'nombre demasiado largo' => [['player' => str_repeat('a', 31), 'points' => 10]],
            'nombre que no es texto' => [['player' => ['Diego'], 'points' => 10]],
            'sin puntos' => [['player' => 'Diego']],
            'puntos como texto' => [['player' => 'Diego', 'points' => '120']],
            'puntos negativos' => [['player' => 'Diego', 'points' => -5]],
            'aciertos negativos' => [['player' => 'Diego', 'points' => 10, 'hits' => -1]],
        ];
    }
}