<?php

declare(strict_types=1);

namespace App;

final class ScoreInput
{
    private function __construct(
        public readonly string $player,
        public readonly int $points,
        public readonly int $hits,
    ) {
    }

    public static function fromArray(array $data): ?self
    {
        $player = is_string($data['player'] ?? null) ? trim($data['player']) : '';
        $points = $data['points'] ?? null;
        $hits = $data['hits'] ?? 0;

        if (
            $player === '' || mb_strlen($player) > 30
            || !is_int($points) || $points < 0
            || !is_int($hits) || $hits < 0
        ) {
            return null;
        }

        return new self($player, $points, $hits);
    }
}