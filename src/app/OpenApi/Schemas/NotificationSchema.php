<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: 'Notification'
)]
final class NotificationSchema
{
    #[OA\Property(
      format: 'uuid'
    )]
    public string $id;

    #[OA\Property]
    public string $subscriber_id;

    #[OA\Property(
      enum: [NotificationChannel::Sms->value, NotificationChannel::Email->value]
    )]
    public string $channel;

    #[OA\Property(
      enum: [NotificationType::Transactional->value, NotificationType::Bulk->value]
    )]
    public string $type;

    #[OA\Property]
    public string $recipient;

    #[OA\Property]
    public string $message;

    #[OA\Property(
      enum: [
        NotificationStatus::Queued->value,
        NotificationStatus::Sent->value,
        NotificationStatus::Delivered->value,
        NotificationStatus::Dropped->value,
      ]
    )]
    public string $status;

    #[OA\Property]
    public string $created_at;
}