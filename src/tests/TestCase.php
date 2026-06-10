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

        if (app()->environment('testing')) {
            Redis::connection('testing')->flushdb();
        }
    }
}
