<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

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
      enum: ['sms', 'email']
    )]
    public string $channel;

    #[OA\Property(
      enum: ['transactional', 'bulk']
    )]
    public string $type;

    #[OA\Property]
    public string $recipient;

    #[OA\Property]
    public string $message;

    #[OA\Property(
      enum: [
        'queued',
        'processing',
        'sent',
        'failed'
      ]
    )]
    public string $status;

    #[OA\Property]
    public string $created_at;
}