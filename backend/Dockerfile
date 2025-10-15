FROM php:8.2-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS git unzip libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --prefer-dist --no-progress

COPY . .

RUN if [ ! -f .env ]; then cp .env.example .env; fi

RUN mkdir -p /var/www/logs && chmod 777 /var/www/logs

CMD ["sh", "-c", "if [ ! -d vendor ]; then composer install --no-interaction --no-dev --prefer-dist; fi && if [ ! -f .env ]; then cp .env.example .env; fi && php-fpm -F"]