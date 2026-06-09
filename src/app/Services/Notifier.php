<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\ChannelInterface;
use App\Exceptions\ChannelNotFoundException;
use App\Models\Notification;

class Notifier
{
    /** @var array<string, ChannelInterface> */
    private array $channels = [] {
        get {
            return $this->channels;
        }
    }

    public function addChannel(ChannelInterface $channel): void
    {
        $this->channels[$channel->getName()] = $channel;
    }

    public function send(Notification $notification): void
    {
        $channel = $this->channels[$notification->channel]
          ?? throw new ChannelNotFoundException("Channel [{$notification->channel}] not registered");

        $channel->send($notification);
    }
}