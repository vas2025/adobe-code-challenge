FROM php:8.2-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS git unzip libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --prefer-dist --no-progress

COPY . .

RUN if [ ! -f ./backend/.env ]; then \
      cp ./backend/.env.example ./backend/.env; \
    fi

EXPOSE 9000
CMD ["php-fpm", "-F"]