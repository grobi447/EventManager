FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --optimize-autoloader --ignore-platform-reqs

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]