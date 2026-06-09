<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Requests\SendBulkNotificationRequest;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\DeduplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function __construct(private DeduplicationService $dedup) {}

    /**
     * POST /api/notifications/send
     */
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

        $queue = $data['type']==='transactional'
          ? 'notifications.critical'
          : 'notifications.bulk';

        $created = [];

        foreach ($data['recipients'] as $recipient) {
            // Уникальный ключ на уровне одного получателя в рамках batch
            $itemKey = $batchKey . ':' . $recipient['id'];

            $notification = Notification::create([
              'id'              => Str::uuid(),
              'idempotency_key' => $itemKey,
              'subscriber_id'   => $recipient['id'],
              'channel'         => $data['channel'],
              'type'            => $data['type'],
              'message'         => $data['message'],
              'recipient'       => $recipient['address'],
              'status'          => 'queued',
            ]);

            // Отправляем в нужную очередь
            SendNotificationJob::dispatch($notification->id)
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

    /**
     * GET /api/subscribers/{subscriberId}/notifications
     */
    public function history(string $subscriberId): JsonResponse
    {
        $notifications = Notification::where('subscriber_id', $subscriberId)
                                     ->orderByDesc('created_at')
                                     ->paginate(50);

        return response()->json($notifications);
    }

    /**
     * GET /api/notifications/{id}
     */
    public function show(string $id): JsonResponse
    {
        $notification = Notification::findOrFail($id);

        return response()->json($notification);
    }
}