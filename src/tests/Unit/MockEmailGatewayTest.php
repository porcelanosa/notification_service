<?php

declare(strict_types = 1);

namespace Tests\Unit;

use App\Gateways\MockEmailGateway;
use Tests\TestCase;

class MockEmailGatewayTest extends TestCase
{
    private MockEmailGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new MockEmailGateway();
    }

    public function test_returns_success_for_valid_email(): void
    {
        $result = $this->gateway->deliver('user@example.com', 'Hello!');

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getFailureReason());
    }

    public function test_returns_failure_for_invalid_email(): void
    {
        $result = $this->gateway->deliver('not-an-email', 'Hello!');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid email address', $result->getFailureReason());
    }

    public function test_returns_failure_for_empty_email(): void
    {
        $result = $this->gateway->deliver('', 'Hello!');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid email address', $result->getFailureReason());
    }

    public function test_returns_failure_for_email_without_domain(): void
    {
        $result = $this->gateway->deliver('user@', 'Hello!');

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid email address', $result->getFailureReason());
    }

    public function test_delivers_with_any_message_content(): void
    {
        $result = $this->gateway->deliver('user@example.com', '');
        $this->assertTrue($result->isSuccess());

        $result = $this->gateway->deliver('user@example.com', str_repeat('a', 1000));
        $this->assertTrue($result->isSuccess());
    }
}