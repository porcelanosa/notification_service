# Notification Service

## Техническое задание

[Полный текст ТЗ](TZ.md)

## Запуск

```bash
git clone <repo> && cd notification-service

# 1. Создай .env в папке src/
cp src/.env.example src/.env

# 2. Запусти всё
docker-compose up -d --build

# 3. Инициализация приложения (первый раз)
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate

# 4. Готово! API доступен на http://localhost:8080
```

## Запуск тестов

```bash
docker-compose exec app php artisan test --filter=NotificationFlowTest
```

## RabbitMQ

```shell
http://localhost:15672/
```
**Логин/пароль:**

rabbitmq_user

qwerty123!wq

## Swagger UI

Генерация Swagger схемы
```shell
docker-compose exec app php artisan l5-swagger:generate

```
Вход в UI 
```
http://localhost:8080/api/documentation
```