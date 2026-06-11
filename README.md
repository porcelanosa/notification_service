# Notification Service

## Техническое задание

[Полный текст ТЗ](TZ.md)

## Запуск

```
git clone git@github.com:porcelanosa/notification_service.git && cd notification-service
```

1. Копируем .env в папке src/ – пароли уже все в соответствии с docker

```cp src/.env.example src/.env```

2. Поднимаем докер-контейнеры

```docker-compose up -d --build```

3. Устанавливаем PHP-зависимости (composer) внутри контейнера app

```docker-compose exec app composer install```

4. Инициализация приложения (первый раз)
```
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

5. API доступен на http://localhost:8080


## Запуск тестов

```bash
docker-compose exec app php artisan test
```
или только интеграционные тесты

```bash
docker-compose exec app php artisan test --filter=NotificationFlowTest
```

## RabbitMQ

```shell
http://localhost:15672/
```
**Логин/пароль:**

`rabbitmq_user`

`qwerty123!wq`

## Swagger UI

Генерация Swagger схемы
```shell
docker-compose exec app php artisan l5-swagger:generate

```
Вход в UI 
```
http://localhost:8080/api/documentation
```

## Ручное тестирование

[Пошаговый план ручного тестирования](docs/TESTING_PLAN.md)