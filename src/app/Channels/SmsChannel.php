<?php

declare(strict_types = 1);

namespace App\Channels;

use App\Contracts\ChannelInterface;
use App\Contracts\GatewayInterface;
use App\Enums\NotificationChannel;
use App\Exceptions\GatewayUnavailableException;
use App\Models\Notification;

class SmsChannel implements ChannelInterface
{
    public function __construct(private GatewayInterface $gateway) {}

    public function getName(): string { return NotificationChannel::Sms->value; }

    public function send(Notification $notification): void
    {
        $result = $this->gateway->deliver(
          $notification->recipient,
          $notification->message,
        );

        if (!$result->isSuccess()) {
            throw new \App\Exceptions\InvalidRecipientException(
              $result->getFailureReason()
            );
        }
    }
}