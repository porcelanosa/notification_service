<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: 'SendNotificationRequest',
  required: [
    'channel',
    'type',
    'message',
    'idempotency_key',
    'recipients',
  ]
)]
final class SendNotificationRequest
{
    #[OA\Property(
      example: NotificationChannel::Sms->value,
      enum   : [NotificationChannel::Sms->value, NotificationChannel::Email->value]
    )]
    public string $channel;

    #[OA\Property(
      example: NotificationType::Bulk->value,
      enum   : [NotificationType::Transactional->value, NotificationType::Bulk->value]
    )]
    public string $type;

    #[OA\Property(
      example: 'Ваш код: 1234'
    )]
    public string $message;

    #[OA\Property(
      example: 'batch-uuid-001'
    )]
    public string $idempotency_key;

    #[OA\Property(
      type: 'array',
      items: new OA\Items(
        properties: [
          new OA\Property(
            property: 'id',
            type: 'string',
            example: 'user-1'
          ),
          new OA\Property(
            property: 'address',
            type: 'string',
            example: '+79001234567'
          ),
        ],
        type: 'object'
      )
    )]
    public array $recipients;
}