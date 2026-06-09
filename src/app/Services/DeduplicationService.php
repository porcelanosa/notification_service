<?php

declare(strict_types = 1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class DeduplicationService
{
    private int $ttl;

    public function __construct()
    {
        $this->ttl = (int)config('app.dedup_ttl', 86400);
    }

    /**
     * Возвращает true если ключ уже видели (дубликат).
     */
    public function isDuplicate(string $idempotencyKey): bool
    {
        $redisKey = "dedup:{$idempotencyKey}";
        // SET NX — атомарно, возвращает 1 если ключ был создан (первый раз)
        $set = Redis::set($redisKey, 1, 'EX', $this->ttl, 'NX');

        return $set===null; // null = ключ уже существовал
    }
}