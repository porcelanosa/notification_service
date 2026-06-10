<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
  version    : '1.0.0',
  description: 'API массовой отправки уведомлений',
  title      : 'Notification Service API'
)]
#[OA\Server(
  url: 'http://localhost:8080',
  description: 'Local'
)]
#[OA\Tag(
  name: 'Notifications',
  description: 'Работа с уведомлениями'
)]
final class OpenApiSpec
{
}