<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: 'AcceptedResponse'
)]
final class AcceptedResponse
{
    #[OA\Property(example: 10)]
    public int $accepted;

    #[OA\Property(example: 'notifications.bulk')]
    public string $queue;

    #[OA\Property(example: 'batch-uuid-001')]
    public string $idempotency_key;

    #[OA\Property(
      type: 'array',
      items: new OA\Items(
        type: 'string',
        format: 'uuid'
      )
    )]
    public array $notification_ids;
}