<?php

declare(strict_types = 1);

namespace App\Gateways;

use App\Contracts\DeliveryResultInterface;
use App\Contracts\GatewayInterface;
use App\DTO\DeliveryResult;
use Illuminate\Support\Facades\Log;

class MockEmailGateway implements GatewayInterface
{
    public function deliver(string $recipient, string $message): DeliveryResultInterface
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return DeliveryResult::failure('Invalid email address');
        }

        Log::channel('single')->info("[MockEmail] Sent to {$recipient}: {$message}");
        return DeliveryResult::success();
    }
}