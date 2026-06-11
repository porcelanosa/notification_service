<?php

declare(strict_types = 1);

namespace Tests\Unit;

use App\Services\DeduplicationService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class DeduplicationServiceTest extends TestCase
{
    private DeduplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DeduplicationService();
    }

    protected function getRedisKey(string $key): string
    {
        return 'dedup:' . $key;
    }

    public function test_first_request_is_not_duplicate(): void
    {
        $result = $this->service->isDuplicate('unique-key-001');

        $this->assertFalse($result);
    }

    public function test_second_request_with_same_key_is_duplicate(): void
    {
        $this->service->isDuplicate('unique-key-002');

        $result = $this->service->isDuplicate('unique-key-002');

        $this->assertTrue($result);
    }

    public function test_different_keys_are_not_duplicates(): void
    {
        $this->service->isDuplicate('key-aaa');
        $this->service->isDuplicate('key-bbb');

        // Каждый ключ первый раз — не дубликат
        $this->assertFalse($this->service->isDuplicate('key-ccc'));
    }

    public function test_key_is_stored_in_redis_after_first_call(): void
    {
        $this->service->isDuplicate('key-to-check');

        // Debug: check what connection the service uses
        $redis = Redis::connection('testing');
        $allKeys = $redis->keys('*');
        \Log::channel('single')->info('Test: All keys in testing DB: ' . json_encode($allKeys));

        $exists = $redis->exists($this->getRedisKey('key-to-check'));

        $this->assertEquals(1, $exists);
    }

    public function test_redis_key_has_ttl_set(): void
    {
        $this->service->isDuplicate('key-with-ttl');

        $ttl = Redis::connection('testing')->ttl($this->getRedisKey('key-with-ttl'));

        // TTL должен быть установлен (> 0)
        $this->assertGreaterThan(0, $ttl);
    }

    public function test_redis_key_ttl_matches_config(): void
    {
        $expectedTtl = (int) config('app.dedup_ttl', 86400);

        $this->service->isDuplicate('key-ttl-match');

        $ttl = Redis::connection('testing')->ttl($this->getRedisKey('key-ttl-match'));

        // TTL не должен превышать настроенное значение
        $this->assertLessThanOrEqual($expectedTtl, $ttl);
        // И должен быть близок к нему (с погрешностью 5 секунд)
        $this->assertGreaterThan($expectedTtl - 5, $ttl);
    }

    public function test_multiple_calls_do_not_reset_ttl(): void
    {
        $this->service->isDuplicate('key-no-reset');
        $ttlAfterFirst = Redis::connection('testing')->ttl($this->getRedisKey('key-no-reset'));

        sleep(1);

        $this->service->isDuplicate('key-no-reset'); // повторный вызов
        $ttlAfterSecond = Redis::connection('testing')->ttl($this->getRedisKey('key-no-reset'));

        // TTL уменьшился
        $this->assertLessThan($ttlAfterFirst, $ttlAfterSecond);
    }
}