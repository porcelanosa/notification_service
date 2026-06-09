<?php

declare(strict_types = 1);

namespace App\Contracts;

use App\Models\Notification;

interface ChannelInterface
{
    public function getName(): string;

    /**
     * @throws \App\Exceptions\GatewayUnavailableException
     * @throws \App\Exceptions\InvalidRecipientException
     */
    public function send(Notification $notification): void;
}