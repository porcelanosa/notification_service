<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
      'id',
      'idempotency_key',
      'subscriber_id',
      'channel',
      'type',
      'message',
      'recipient',
      'status',
      'failure_reason',
      'attempts',
    ];

    protected function casts(): array
    {
        return [
          'channel' => NotificationChannel::class,
          'type'    => NotificationType::class,
          'status'  => NotificationStatus::class,
        ];
    }
}