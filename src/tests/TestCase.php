<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force testing environment
        $this->app['env'] = 'testing';
        $this->app->instance('env', 'testing');
        config(['app.env' => 'testing']);

        // Flush testing Redis DB
        Redis::connection('testing')->flushdb();
    }
}
