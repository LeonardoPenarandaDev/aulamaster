# syntax=docker/dockerfile:1

# ---- Etapa 1: instalar dependencias de PHP con Composer ----
FROM composer:2 AS composer-build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Etapa 2: compilar los assets de Vue/Vite ----
# resources/js/app.js importa Ziggy desde vendor/tightenco/ziggy (paquete PHP,
# no npm), así que necesita el vendor/ de Composer antes de poder buildear.
FROM node:20-alpine AS node-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=composer-build /app/vendor ./vendor
RUN npm run build

# ---- Etapa 3: imagen final (PHP-FPM + Nginx + Supervisor en un solo contenedor) ----
FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor unzip libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer-build /app /var/www/html
COPY --from=node-build /app/public/build /var/www/html/public/build

COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
