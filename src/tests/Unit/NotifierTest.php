<?php

declare(strict_types = 1);

namespace Tests\Unit;

use App\Contracts\ChannelInterface;
use App\Enums\NotificationChannel;
use App\Exceptions\ChannelNotFoundException;
use App\Models\Notification;
use App\Services\Notifier;
use Tests\TestCase;

class NotifierTest extends TestCase
{
    public function test_routes_to_correct_channel(): void
    {
        $smsChannel = $this->createMock(ChannelInterface::class);
        $smsChannel->method('getName')->willReturn(NotificationChannel::Sms->value);
        $smsChannel->expects($this->once())->method('send');

        $notifier = new Notifier();
        $notifier->addChannel($smsChannel);

        $notification = new Notification(['channel' => NotificationChannel::Sms->value]);
        $notifier->send($notification);
    }

    public function test_throws_if_channel_not_registered(): void
    {
        $this->expectException(ChannelNotFoundException::class);

        $notifier = new Notifier();
        // Use stdClass to bypass Eloquent enum casting
        $notification = new class {
            public string $channel = 'unknown';
        };
        $notifier->send($notification);
    }
}