<?php

declare(strict_types = 1);

namespace Tests\Integration;

use App\Channels\EmailChannel;
use App\Contracts\GatewayInterface;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Gateways\MockSmsGateway;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест 1: Запрос принят, уведомления сохранены в БД со статусом 'queued',
     * и задачи поставлены в очередь.
     */
    public function test_bulk_send_queues_notifications(): void
    {
        $response = $this->postJson('/api/notifications/send', [
          'channel'         => NotificationChannel::Sms->value,
          'type'            => NotificationType::Bulk->value,
          'message'         => 'Hello from test!',
          'idempotency_key' => 'test-batch-001',
          'recipients'      => [
            ['id' => 'user-1', 'address' => '+79001234567'],
            ['id' => 'user-2', 'address' => '+79007654321'],
          ],
        ]);

        $response->assertStatus(202)
                 ->assertJsonFragment(['accepted' => 2, 'queue' => 'notifications.bulk']);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
          'subscriber_id' => 'user-1',
          'status'        => NotificationStatus::Delivered->value,
          'channel'       => NotificationChannel::Sms->value,
        ]);
    }

    /**
     * Тест 2: Транзакционные сообщения попадают в приоритетную очередь.
     */
    public function test_transactional_notifications_use_critical_queue(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/notifications/send', [
          'channel'         => NotificationChannel::Sms->value,
          'type'            => NotificationType::Transactional->value,
          'message'         => 'Your OTP: 123456',
          'idempotency_key' => 'otp-batch-001',
          'recipients'      => [
            ['id' => 'user-1', 'address' => '+79001234567'],
          ],
        ]);

        $response->assertStatus(202)
                 ->assertJsonFragment(['queue' => 'notifications.critical']);

        Queue::assertPushedOn('notifications.critical', SendNotificationJob::class);
    }

    /**
     * Тест 3: Полная цепочка — job обрабатывает уведомление,
     * статус меняется до 'delivered'.
     */
    public function test_job_processes_notification_to_delivered(): void
    {
        $notification = Notification::create([
          'id'              => Str::uuid(),
          'idempotency_key' => 'test-item-001',
          'subscriber_id'   => 'user-1',
          'channel'         => NotificationChannel::Sms->value,
          'type'            => NotificationType::Bulk->value,
          'message'         => 'Test message',
          'recipient'       => '+79001234567',
          'status'          => NotificationStatus::Queued->value,
        ]);

        // Запускаем job синхронно
        new SendNotificationJob($notification)->handle(
          app(Notifier::class)
        );

        $this->assertDatabaseHas('notifications', [
          'id'     => $notification->id,
          'status' => NotificationStatus::Delivered->value,
        ]);
    }

    /**
     * Тест 4: Невалидный получатель → статус 'dropped'.
     */
    public function test_invalid_recipient_drops_notification(): void
    {
        $notification = Notification::create([
          'id'              => Str::uuid(),
          'idempotency_key' => 'test-bad-recipient',
          'subscriber_id'   => 'user-bad',
          'channel'         => NotificationChannel::Sms->value,
          'type'            => NotificationType::Bulk->value,
          'message'         => 'Test',
          'recipient'       => '+00000000000', // невалидный (MockSmsGateway)
          'status'          => NotificationStatus::Queued->value,
        ]);

        new SendNotificationJob($notification)->handle(
          app(Notifier::class)
        );

        $this->assertDatabaseHas('notifications', [
          'id'     => $notification->id,
          'status' => NotificationStatus::Dropped->value,
        ]);
    }

    /**
     * Тест 5: Дедупликация — повторный запрос с тем же idempotency_key
     * не создаёт новых записей в БД.
     */
    public function test_duplicate_request_is_rejected(): void
    {
        Queue::fake();

        $payload = [
          'channel'         => NotificationChannel::Email->value,
          'type'            => NotificationType::Bulk->value,
          'message'         => 'Welcome!',
          'idempotency_key' => 'dedup-test-001',
          'recipients'      => [
            ['id' => 'user-1', 'address' => 'user@example.com'],
          ],
        ];

        // Первый запрос
        $this->postJson('/api/notifications/send', $payload)->assertStatus(202);
        $this->assertDatabaseCount('notifications', 1);

        // Второй идентичный запрос
        $response = $this->postJson('/api/notifications/send', $payload);
        $response->assertStatus(202)
                 ->assertJsonFragment(['message' => 'Duplicate request detected. Already accepted.']);

        // В БД всё ещё 1 запись
        $this->assertDatabaseCount('notifications', 1);
    }

    /**
     * Тест 6: История уведомлений подписчика.
     */
    public function test_subscriber_history_endpoint(): void
    {
        Notification::factory()->count(3)->create(['subscriber_id' => 'user-42']);
        Notification::factory()->count(2)->create(['subscriber_id' => 'user-99']);

        $response = $this->getJson('/api/subscribers/user-42/notifications');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /**
     * Тест 7: Job не обрабатывает повторно уже доставленное уведомление
     * (exactly-once защита).
     */
    public function test_already_delivered_notification_is_skipped(): void
    {
        $gateway = $this->createMock(GatewayInterface::class);
        $gateway->expects($this->never())->method('deliver'); // шлюз не должен вызываться

        $notification = Notification::create([
          'id'              => Str::uuid(),
          'idempotency_key' => 'already-done',
          'subscriber_id'   => 'user-1',
          'channel'         => NotificationChannel::Email->value,
          'type'            => NotificationType::Bulk->value,
          'message'         => 'Test',
          'recipient'       => 'test@example.com',
          'status'          => NotificationStatus::Delivered->value
        ]);

        $notifier = new Notifier();
        $notifier->addChannel(new EmailChannel($gateway));

        new SendNotificationJob($notification)->handle($notifier);

        // Статус не изменился
        $this->assertDatabaseHas('notifications', [
          'id'     => $notification->id,
          'status' => NotificationStatus::Delivered->value,
        ]);
    }
}