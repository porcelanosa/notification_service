FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    rabbitmq-c-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql pcntl \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html