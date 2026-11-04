FROM php:8.4-fpm-alpine AS base

# Cleanly pull the extension installer straight from its official image layer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install the imagick extension and ghostscript system package safely
RUN install-php-extensions imagick && \
    apk add --no-cache ghostscript

# Install system dependencies & base PHP core extensions (including bcmath)
RUN apk --update add --no-cache mariadb-client mariadb-connector-c openssl mysql-client curl git libxml2-dev libzip-dev zip oniguruma-dev libpng-dev \
    && docker-php-ext-install pdo_mysql zip xml mbstring bcmath exif gd

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

ENV COMPOSER_HOME=./.composer
COPY --from=composer:2.7.7 /usr/bin/composer /usr/bin/composer

FROM base AS deps

COPY composer.json /var/www/html/composer.json

RUN composer install --no-dev --no-autoloader --no-scripts

FROM base AS prod

COPY --chown=www-data:www-data . /var/www/html
COPY --chown=www-data:www-data --from=deps /var/www/html/vendor /var/www/html/vendor
COPY policy.xml /etc/ImageMagick-7/policy.xml

USER www-data
RUN mkdir -p /var/www/html/bootstrap/cache
RUN composer dump-autoload --optimize
RUN chmod -R 775 /var/www/html/storage
RUN ln -sf /dev/stdout /var/www/html/storage/logs/laravel.log
