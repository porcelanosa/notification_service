<?php

declare(strict_types=1);

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: 'ValidationErrorResponse'
)]
final class ValidationErrorResponse
{
    #[OA\Property(
      example: 'The given data was invalid.'
    )]
    public string $message;

    #[OA\Property(
      type: 'object'
    )]
    public object $errors;
}