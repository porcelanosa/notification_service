<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\ChannelInterface;
use App\Exceptions\ChannelNotFoundException;
use App\Models\Notification;

class Notifier
{
    /** @var array<string, ChannelInterface> */
    private array $channels;

    public function __construct() { $this->channels = []; }

    public function addChannel(ChannelInterface $channel): void
    {
        $this->channels[$channel->getName()] = $channel;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function send(Notification $notification): void
    {
        $channelName = $notification->channel instanceof \BackedEnum
            ? $notification->channel->value
            : $notification->channel;

        $channel = $this->channels[$channelName]
          ?? throw new ChannelNotFoundException("Канал [{$channelName}] не зарегистрирова");

        $channel->send($notification);
    }
}