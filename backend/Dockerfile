FROM php:8.2-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS git unzip libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --prefer-dist --no-progress

COPY . .

COPY backend/.env backend/.env.example

EXPOSE 9000
CMD ["php-fpm", "-F"]