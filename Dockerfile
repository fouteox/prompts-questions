FROM composer:latest AS builder

WORKDIR /app

COPY composer.json .

RUN composer install \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-dev \
    --prefer-dist

FROM php:8.4-cli-alpine

WORKDIR /app

COPY --from=builder /app/vendor ./vendor

COPY questions.php /app

ENTRYPOINT ["php", "/app/questions.php"]