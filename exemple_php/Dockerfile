FROM php:8.3-apache

# Installation des dépendances système nécessaires à PostgreSQL
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY ./src /var/www/html