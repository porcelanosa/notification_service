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
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30; // секунды между попытками

    public function __construct(public readonly string $notificationId) {}

    public function handle(Notifier $notifier): void
    {
        $notification = Notification::findOrFail($this->notificationId);

        // Защита от повторной обработки уже доставленного (at-least-once → exactly-once)
        if (in_array(
          $notification->status,
          [NotificationStatus::Delivered->value, NotificationStatus::Dropped->value],
          true,
        )) {
            Log::info("Notification {$this->notificationId} already processed, skipping.");

            return;
        }

        $notification->increment('attempts');

        try {
            $notifier->send($notification);

            $notification->update(['status' => NotificationStatus::Sent->value]);

            // В реальном сервисе статус 'delivered' подтверждается webhook-ом провайдера.
            // В нашем моке — сразу переводим в delivered.
            $notification->update(['status' => NotificationStatus::Delivered->value]);
        } catch (InvalidRecipientException $e) {
            // Ретраи бессмысленны — получатель невалиден
            $notification->update([
              'status'         => NotificationStatus::Dropped->value,
              'failure_reason' => $e->getMessage(),
            ]);
            $this->fail($e);
        } catch (GatewayUnavailableException $e) {
            // Временный сбой — позволяем Laravel делать retry
            $notification->update([
              'status'         => NotificationStatus::Queued->value,
              'failure_reason' => $e->getMessage(),
            ]);
            throw $e; // пробрасываем для retry-механизма очереди
        }
    }

    public function failed(\Throwable $e): void
    {
        Notification::where('id', $this->notificationId)
                    ->whereNotIn(
                      'status',
                      [NotificationStatus::Delivered->value, NotificationStatus::Dropped->value],
                    )
                    ->update([
                      'status'         => NotificationStatus::Dropped->value,
                      'failure_reason' => "Max retries exceeded: {$e->getMessage()}",
                    ]);
    }
}