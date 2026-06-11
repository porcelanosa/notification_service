<?php

declare(strict_types = 1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class DeduplicationService
{
    private int $ttl;
    private string $connection;

    public function __construct()
    {
        $this->ttl        = (int) config('app.dedup_ttl', 86400);
        $this->connection = app()->environment('testing') ? 'testing' : 'default';
    }

    /**
     * Возвращает true если ключ уже существует.
     */
    public function isDuplicate(string $idempotencyKey): bool
    {
        $redisKey = "dedup:{$idempotencyKey}";

        $redis      = Redis::connection($this->connection)->client();
        $result     = $redis->set($redisKey, 1, 'EX', $this->ttl, 'NX');

        return $result===null;
    }
}