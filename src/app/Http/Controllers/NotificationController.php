<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Http\Requests\SendBulkNotificationRequest;
use App\Http\Resources\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\DeduplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(
  name: 'Notifications',
  description: 'Управление уведомлениями'
)]
class NotificationController extends Controller
{
    public function __construct(private readonly DeduplicationService $dedup) {}

    #[OA\Post(
      path: '/api/notifications/send',
      operationId: 'sendNotifications',
      summary: 'Массовая отправка уведомлений',
      tags: ['Notifications']
    )]
    #[OA\RequestBody(
      required: true,
      content: new OA\JsonContent(
        ref: '#/components/schemas/SendNotificationRequest'
      )
    )]
    #[OA\Response(
      response: 202,
      description: 'Accepted',
      content: new OA\JsonContent(
        ref: '#/components/schemas/AcceptedResponse'
      )
    )]
    #[OA\Response(
      response: 422,
      description: 'Validation Error',
      content: new OA\JsonContent(
        ref: '#/components/schemas/ValidationErrorResponse'
      )
    )]
    public function send(SendBulkNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Дедупликация на уровне всего batch-запроса
        $batchKey = $data['idempotency_key'];
        if ($this->dedup->isDuplicate($batchKey)) {
            return response()->json([
              'message'         => 'Duplicate request detected. Already accepted.',
              'idempotency_key' => $batchKey,
            ], 202);
        }

        $queue = $data['type'] === NotificationType::Transactional->value
          ? 'notifications.critical'
          : 'notifications.bulk';

        $created = [];

        foreach ($data['recipients'] as $recipient) {
            // Уникальный ключ на уровне одного получателя в рамках batch
            $itemKey = $batchKey . ':' . $recipient['id'];

            // Проверяем дубликат на уровне получателя
            if ($this->dedup->isDuplicate($itemKey)) {
                // Уведомление для этого получателя уже существует — считаем batch дубликатом
                return response()->json([
                  'message'         => 'Duplicate request detected. Already accepted.',
                  'idempotency_key' => $batchKey,
                ], 202);
            }

            $notification = Notification::create([
              'id'              => Str::uuid(),
              'idempotency_key' => $itemKey,
              'subscriber_id'   => $recipient['id'],
              'channel'         => $data['channel'],
              'type'            => $data['type'],
              'message'         => $data['message'],
              'recipient'       => $recipient['address'],
              'status'          => NotificationStatus::Queued->value,
            ]);

            // Отправляем в нужную очередь
            SendNotificationJob::dispatch($notification)
                               ->onQueue($queue);

            $created[] = $notification->id;
        }

        return response()->json([
          'accepted'         => count($created),
          'queue'            => $queue,
          'idempotency_key'  => $batchKey,
          'notification_ids' => $created,
        ], 202);
    }

    #[OA\Get(
      path: '/api/subscribers/{subscriberId}/notifications',
      operationId: 'subscriberNotifications',
      summary: 'История уведомлений подписчика',
      tags: ['Notifications']
    )]
    #[OA\Parameter(
      name: 'subscriberId',
      description: 'ID подписчика',
      in: 'path',
      required: true,
      schema: new OA\Schema(
        type: 'string'
      )
    )]
    #[OA\Response(
      response: 200,
      description: 'Список уведомлений'
    )]
    public function history(string $subscriberId): JsonResponse
    {
        $notifications = Notification::where('subscriber_id', $subscriberId)
                                     ->orderByDesc('created_at')
                                     ->paginate(50);

        return NotificationCollection::make($notifications)->response()->setStatusCode(200);
    }

    #[OA\Get(
      path: '/api/notifications/{id}',
      operationId: 'notificationShow',
      summary: 'Получить уведомление по ID',
      tags: ['Notifications']
    )]
    #[OA\Parameter(
      name: 'id',
      description: 'UUID уведомления',
      in: 'path',
      required: true,
      schema: new OA\Schema(
        type: 'string',
        format: 'uuid'
      )
    )]
    #[OA\Response(
      response: 200,
      description: 'Notification',
      content: new OA\JsonContent(
        ref: '#/components/schemas/Notification'
      )
    )]
    #[OA\Response(
      response: 404,
      description: 'Not found',
      content: new OA\JsonContent(
        ref: '#/components/schemas/NotFoundResponse'
      )
    )]
    public function show(string $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        return new NotificationResource($notification)->response()->setStatusCode(200);
    }
}