# Ручная проверка Notification Service

## Предварительная подготовка

### 1. Проверяем что всё запущено

```bash
docker-compose up -d
docker-compose ps
```

Все сервисы должны быть в статусе `healthy` или `running`:
```
notification-service-app-1              running
notification-service-nginx-1            running
notification-service-worker_critical-1  running
notification-service-worker_bulk-1      running
notification-service-postgres-1         running (healthy)
notification-service-rabbitmq-1         running (healthy)
notification-service-redis-1            running (healthy)
```

### 2. Проверяем, что миграции отработали

```bash
docker-compose exec app php artisan migrate:status
```

---

## Шаг 1 — Проверка инфраструктуры

### RabbitMQ Management UI
Открой в браузере: **http://localhost:15672**
- Login: `rabbitmq_user` / Password: `qwerty123!wq`
- Смотрим, что вкладка **Queues** пуста (очереди появятся после первого запроса)

### Redis
Проверяем, что Redis запущен и  отвечает
```bash
docker-compose exec redis redis-cli -a qwerty123\!wq ping
# Ответ: PONG
```

### PostgreSQL
Проверяем наличие таблицы в PostgreSQL
```bash
docker-compose exec postgres psql -U postgres_db_user -d notifications -c "\dt"
```

---

## Шаг 2 — Настройка Postman


### Postman collection

[Коллекция запросов для Postman](Notification%20Service.postman_collection.json) 

## Шаг 3 — Сценарий 1: Bulk рассылка

### Запрос в Postman

```
POST {{base_url}}/api/notifications/send
Content-Type: application/json
```

```json
{
    "channel": "sms",
    "type": "bulk",
    "message": "Скидка 50% только сегодня!",
    "idempotency_key": "promo-2026-001",
    "recipients": [
        {"id": "user-1", "address": "+79001234567"},
        {"id": "user-2", "address": "+79007654321"},
        {"id": "user-3", "address": "+79003334455"}
    ]
}
```

### Ожидаемый ответ `202`

```json
{
    "accepted": 3,
    "queue": "notifications.bulk",
    "idempotency_key": "promo-2026-001",
    "notification_ids": [
        "uuid-1",
        "uuid-2",
        "uuid-3"
    ]
}
```

### Что проверить после

**В RabbitMQ UI** — вкладка Queues → `notifications.bulk`:
- сообщения появились и быстро исчезли (worker обработал)

**В БД:**
```bash
docker-compose exec postgres psql -U app -d notifications \
  -c "SELECT id, subscriber_id, status, attempts FROM notifications;"
```

Статус должен быть `delivered`.

**В логах worker:**
```bash
docker-compose logs worker_bulk
```

Должны быть строки:
```
worker_bulk-1  |   2026-06-10 17:49:49 App\Jobs\SendNotificationJob ................... RUNNING
worker_bulk-1  |   2026-06-10 17:49:49 App\Jobs\SendNotificationJob .............. 22.66ms DONE
```

---

## Шаг 4 — Сценарий 2: Транзакционное уведомление

```
POST {{base_url}}/api/notifications/send
Content-Type: application/json
```

```json
{
    "channel": "sms",
    "type": "transactional",
    "message": "Ваш код подтверждения: 847291",
    "idempotency_key": "otp-user1-1703001234",
    "recipients": [
        {"id": "user-1", "address": "+79001234567"}
    ]
}
```

### Ожидаемый ответ `202`

```json
{
    "accepted": 1,
    "queue": "notifications.critical",
    ...
}
```

### Что проверить

В RabbitMQ UI смотрим, что сообщение пошло именно в `notifications.critical`, а не в `notifications.bulk`.

---

## Шаг 5 — Сценарий 3: Дедупликация

Отправляем **тот же запрос из Шага 3 повторно** (тот же `idempotency_key: promo-2026-001`).

### Ожидаемый ответ `202`

```json
{
    "message": "Duplicate request detected. Already accepted.",
    "idempotency_key": "promo-2026-001"
}
```

### Что проверить в БД

```bash
docker-compose exec postgres psql -U app -d notifications \
  -c "SELECT COUNT(*) FROM notifications WHERE idempotency_key LIKE 'promo-2026-001%';"
```

Должно быть **3** — новые записи не создались.

### Что проверить в Redis

```bash
docker-compose exec redis redis-cli keys "dedup:*"
# dedup:promo-2026-001

docker-compose exec redis redis-cli ttl "dedup:promo-2026-001"
# Оставшееся время жизни ключа в секундах
```

---

## Шаг 6 — Сценарий 4: Невалидный получатель



```json
{
    "channel": "sms",
    "type": "bulk",
    "message": "Тест",
    "idempotency_key": "test-invalid-001",
    "recipients": [
        {"id": "user-bad", "address": "+00000000000"}
    ]
}
```

### Проверяем в БД

```bash
docker-compose exec postgres psql -U app -d notifications \
  -c "SELECT status, failure_reason FROM notifications WHERE subscriber_id = 'user-bad';"
```

Ожидаемый результат:
```
  status  |     failure_reason
----------+------------------------
 dropped  | Invalid phone number
```

---

## Шаг 7 — Сценарий 5: Статус конкретного уведомления

Возьми любой `id` из ответа Шага 3 и подставь:

```
GET {{base_url}}/api/notifications/{{notification_id}}
```

### Ожидаемый ответ `200`

```json
{
  "id": "ccf21e0f-7322-4120-a0d4-e0d2706198be",
  "idempotency_key": "test-002:user-2",
  "subscriber_id": "user-2",
  "channel": "sms",
  "type": "bulk",
  "message": "Test message 2",
  "recipient": "+79001234567",
  "status": "dropped",
  "failure_reason": "Максимальное количество запросов достигнуто: Cannot access offset of type App\\Enums\\NotificationChannel on array",
  "attempts": 3,
  "created_at": "2026-06-10T13:38:58.000000Z",
  "updated_at": "2026-06-10T13:40:00.000000Z"
}
```

---

## Шаг 8 — Сценарий 6: История подписчика

```
GET {{base_url}}/api/subscribers/user-1/notifications
```

### Ожидаемый ответ `200`

```json
{
  "data": [
    ...
  ],
  "current_page": 1,
  "first_page_url": "http://localhost:8080/api/subscribers/user-1/notifications?page=1",
  "from": 1,
  "last_page": 1,
  "last_page_url": "http://localhost:8080/api/subscribers/user-1/notifications?page=1"
  "links": [
    ...
  ],
  "next_page_url": null,
  "path": "http://localhost:8080/api/subscribers/user-1/notifications",
  "per_page": 50,
  "prev_page_url": null,
  "to": 9,
  "total": 9
}
```

---

## Шаг 9 — Сценарий 7: Валидация запроса

Отправь запрос с невалидными данными:

```json
{
    "channel": "telegram",
    "type": "unknown",
    "message": "",
    "idempotency_key": "",
    "recipients": []
}
```

### Ожидаемый ответ `422`

```json
{
  "message": "Validation failed",
  "errors": {
    "channel": [
      "The selected channel is invalid."
    ],
    "type": [
      "The selected type is invalid."
    ],
    "message": [
      "The message field is required."
    ],
    "idempotency_key": [
      "The idempotency key field is required."
    ],
    "recipients": [
      "The recipients field is required."
    ]
  }
}
```

---

## Шаг 10 — Наблюдение за очередями в реальном времени

Открой три терминала одновременно:

**Терминал 1 — логи workers:**
```bash
docker-compose logs -f worker_critical worker_bulk
```

**Терминал 2 — состояние БД в реальном времени:**
```bash
watch -n 1 'docker-compose exec postgres psql -U postgres_db_user -d notifications \
  -c "SELECT subscriber_id, channel, type, status, attempts FROM notifications ORDER BY created_at DESC LIMIT 10;"'
```

**Терминал 3 — Redis ключи:**
```bash
watch -n 1 'docker compose exec redis redis-cli -a "qwerty123!wq" --no-auth-warning keys "dedup:*"'
watch -n 1 'docker compose exec redis redis-cli -a "qwerty123!wq" --no-auth-warning -n 0 keys "*dedup*"'
```

Затем делаем запросы из Postman и смотрим как данные проходят через всю цепочку в реальном времени.

---

## Swagger UI

Все эндпоинты можно также протестировать через:

```
http://localhost:8080/api/documentation
```

