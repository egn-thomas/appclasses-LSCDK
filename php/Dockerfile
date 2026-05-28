# php/Dockerfile
FROM php:8.3-apache

# Installer les dépendances nécessaires pour l'extension MongoDB et curl (proxy API)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libssl-dev \
    pkg-config \
    unzip \
    curl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install opcache pdo \
    && rm -rf /var/lib/apt/lists* \
    && a2enmod rewrite

COPY php.ini /usr/local/etc/php/conf.d/custom.ini

WORKDIR /var/www/html
EXPOSE 80
