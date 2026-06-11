<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string                                  $id               UUID первичного ключа
 * @property string|null                             $idempotency_key  Ключ идемпотентности (для предотвращения дубликатов)
 * @property string|null                             $subscriber_id    Идентификатор подписчика (клиента)
 * @property NotificationChannel                     $channel          Канал отправки (sms, email, push и т.д.)
 * @property NotificationType                        $type             Тип уведомления (bulk, single и т.п.)
 * @property string                                  $message          Текст сообщения
 * @property string                                  $recipient        Адрес получателя (номер телефона, email и т.д.)
 * @property NotificationStatus                      $status           Текущий статус отправки (pending, sent, failed и т.д.)
 * @property string|null                             $failure_reason   Причина ошибки (если status = failed)
 * @property int                                     $attempts         Количество попыток отправки
 * @property \Illuminate\Support\Carbon|null         $created_at       Дата создания записи
 * @property \Illuminate\Support\Carbon|null         $updated_at       Дата обновления записи
 *
 * @method static Builder|Notification newModelQuery()
 * @method static Builder|Notification newQuery()
 * @method static Builder|Notification query()
 */
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