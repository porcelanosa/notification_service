<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Exceptions\GatewayUnavailableException;
use App\Exceptions\InvalidRecipientException;
use App\Models\Notification;
use App\Services\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // секунды между попытками

    public function __construct(public readonly Notification $notification) {}

    public function handle(Notifier $notifier): void
    {
        $notification = $this->notification;

        // Защита от повторной обработки уже доставленного (at-least-once → exactly-once)
        $statusValue = $notification->status instanceof \BackedEnum
            ? $notification->status->value
            : $notification->status;

        if (in_array(
            $statusValue,
            [NotificationStatus::Delivered->value, NotificationStatus::Dropped->value],
            true
        )) {
            Log::info("Сообщение {$this->notification->id} уже обработано.");

            return;
        }

        $notification->increment('attempts');

        try {
            $notifier->send($notification);

            $notification->update(['status' => NotificationStatus::Sent->value]);

            // В моке — сразу переводим в delivered.
            $notification->update(['status' => NotificationStatus::Delivered->value]);
        } catch (InvalidRecipientException $e) {
            // получатель невалиден
            $notification->update([
              'status'         => NotificationStatus::Dropped->value,
              'failure_reason' => $e->getMessage(),
            ]);
            $this->fail($e);
        } catch (GatewayUnavailableException $e) {
            // Временный сбой — делаем retry
            $notification->update([
              'status'         => NotificationStatus::Queued->value,
              'failure_reason' => $e->getMessage(),
            ]);
            throw $e; // пробрасываем для retry-механизма очереди
        }
    }

    public function failed(\Throwable $e): void
    {
        Notification::where('id', $this->notification->id)
                    ->whereNotIn(
                      'status',
                      [NotificationStatus::Delivered->value, NotificationStatus::Dropped->value],
                    )
                    ->update([
                      'status'         => NotificationStatus::Dropped->value,
                      'failure_reason' => "Максимальное количество запросов достигнуто: {$e->getMessage()}",
                    ]);
    }
}