FROM php:8.4-cli-alpine

RUN apk add --no-cache composer

WORKDIR /app

COPY composer.json .

RUN composer install --no-interaction --no-progress --optimize-autoloader

COPY example.php .

ENTRYPOINT ["php", "example.php"]
