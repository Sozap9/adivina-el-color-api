<?php

declare(strict_types=1);

namespace App;

use Predis\Client;

final class Leaderboard
{
    private const KEY = 'leaderboard';

    public function __construct(private Client $redis)
    {
    }

    public function submit(string $player, int $points): void
    {
        $this->redis->zadd(self::KEY, 'GT', [$player => $points]);
    }

    public function top(int $limit = 10): array
    {
        $scores = $this->redis->zrevrange(self::KEY, 0, $limit - 1, ['withscores' => true]);

        $top = [];
        $position = 1;
        foreach ($scores as $player => $points) {
            $top[] = [
                'position' => $position++,
                'player' => (string) $player,
                'points' => (int) $points,
            ];
        }

        return $top;
    }
}