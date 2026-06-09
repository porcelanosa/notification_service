<?php

declare(strict_types = 1);

namespace App\Gateways;

use App\Contracts\DeliveryResultInterface;
use App\Contracts\GatewayInterface;
use App\DTO\DeliveryResult;
use Illuminate\Support\Facades\Log;

class MockSmsGateway implements GatewayInterface
{
    public function deliver(string $recipient, string $message): DeliveryResultInterface
    {
        // Имитируем невалидный номер
        if (str_starts_with($recipient, '+000')) {
            return DeliveryResult::failure('Invalid phone number');
        }

        // Имитируем случайный сбой шлюза (10% вероятность)
        if (random_int(1, 10) === 1) {
            throw new \App\Exceptions\GatewayUnavailableException('SMS gateway timeout');
        }

        Log::channel('single')->info("[MockSMS] Sent to {$recipient}: {$message}");
        return DeliveryResult::success();
    }
}