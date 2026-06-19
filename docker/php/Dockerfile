# Norlanka — PHP-FPM image for CodeIgniter 4.7 (PHP 8.3+ per the stack spec)
FROM php:8.3-fpm

# System deps for the PHP extensions we need
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (from the official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-norlanka.ini

WORKDIR /var/www/html

# App code is bind-mounted in dev (see docker-compose.yml). For a baked image,
# copy the source and install deps here instead.
EXPOSE 9000
CMD ["php-fpm"]
