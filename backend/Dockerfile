FROM php:8.2-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS git unzip libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www

EXPOSE 9000

CMD ["php-fpm", "-F"]