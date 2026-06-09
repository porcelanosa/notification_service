<?php

declare(strict_types = 1);

namespace App\Contracts;

interface GatewayInterface
{
    public function deliver(string $recipient, string $message): DeliveryResultInterface;
}