# --- Stage 1: Build Frontend Assets ---
FROM node:krypton-alpine AS asset-builder
WORKDIR /app
COPY package.json package-lock.json ./
COPY build ./build
RUN npm ci --include=dev

# --- Stage 2: Build PHP Dependencies ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# --- Stage 3: Final Runtime ---
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    mysqli \
    zip

# Enable Apache modules
RUN a2enmod rewrite headers deflate mime autoindex

# Configure Apache AllowOverride
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

WORKDIR /var/www/html

# Copy application source
COPY . .

# Copy built vendor and jslib directories from build stages
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=asset-builder /app/jslib ./jslib

# Generate the environment-aware connect.inc.php directly inside the build stage
RUN printf '<?php\n$dbHost = getenv("DB_HOST") ?: "db";\n$dbDb   = getenv("DB_NAME") ?: "grr";\n$dbUser = getenv("DB_USER") ?: "grr";\n$dbPass = getenv("DB_PASSWORD") ?: "grr_password";\n$dbPort = getenv("DB_PORT") ?: "3306";\n?>\n' > personnalisation/connect.inc.php

# Create temp directory and set correct permissions
RUN mkdir -p temp \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/personnalisation /var/www/html/temp

CMD ["apache2-foreground"]
