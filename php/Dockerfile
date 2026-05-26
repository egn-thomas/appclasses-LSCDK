# php/Dockerfile
FROM php:8.3-fpm-alpine

# Dépendances système
RUN apk add --no-cache \
    autoconf \
    g++ \
    make \
    openssl-dev \
    pkgconfig

# Extension MongoDB (driver officiel PECL)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Extensions PHP utiles
RUN docker-php-ext-install \
    opcache \
    pdo

# Config PHP de prod
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
