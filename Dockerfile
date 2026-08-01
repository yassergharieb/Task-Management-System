FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libzip-dev \
        mysql-client \
        oniguruma-dev \
        unzip \
        zip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        bcmath \
        intl \
        mbstring \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel-poc.ini

WORKDIR /var/www/html
