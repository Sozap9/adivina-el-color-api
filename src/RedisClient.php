<?php

declare(strict_types=1);

namespace App;

use Predis\Client;

final class RedisClient
{
    public static function connect(): Client
    {
        $url = getenv('REDIS_URL');

        if ($url) {
            return new Client($url);
        }

        return new Client([
            'host' => getenv('REDIS_HOST') ?: 'redis',
            'port' => (int) (getenv('REDIS_PORT') ?: 6379),
        ]);
    }
}