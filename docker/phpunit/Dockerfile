
FROM php:8.1-cli

# Install extensions and utilities
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    wget \
    libzip-dev \
    libonig-dev \
    zip \
    git \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install phpunit PHAR
RUN wget -O /usr/local/bin/phpunit https://phar.phpunit.de/phpunit-9.phar \
    && chmod +x /usr/local/bin/phpunit

WORKDIR /var/www/html
