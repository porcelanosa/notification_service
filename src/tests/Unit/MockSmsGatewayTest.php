<?php

declare(strict_types = 1);

namespace Tests\Unit;

use App\Gateways\MockSmsGateway;
use Tests\TestCase;

class MockSmsGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // отключаем имитацию сбоев
        MockSmsGateway::$simulateFailure = false;
    }

    public function test_returns_failure_for_invalid_number(): void
    {
        $gateway = new MockSmsGateway();
        $result  = $gateway->deliver('+00000000000', 'test');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid phone number', $result->getFailureReason());
    }

    public function test_returns_success_for_valid_number(): void
    {
        $gateway = new MockSmsGateway();
        $result  = $gateway->deliver('+79001234567', 'test');

        $this->assertTrue($result->isSuccess());
    }
}