<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: 'NotFoundResponse'
)]
final class NotFoundResponse
{
    #[OA\Property(
      example: 'Resource not found.'
    )]
    public string $message;
}