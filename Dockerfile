# --- Étape 1 : Construction des ressources frontend ---
FROM node:krypton-alpine AS asset-builder
WORKDIR /app
COPY package.json package-lock.json ./
COPY build ./build
RUN npm ci --include=dev

# --- Étape 2 : Construction des dépendances PHP ---
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# --- Étape 3 : Environnement d'exécution final ---
FROM php:8.5-apache

# Installation des dépendances système nécessaires
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP requises par GRR
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    mysqli \
    zip

# Activation des modules Apache (headers, deflate, rewrite ne sont pas activés par défaut)
RUN a2enmod rewrite headers deflate

# Configuration d'Apache pour autoriser les surcharges .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copie du code source
COPY . .

# Copie des dépendances générées depuis les étapes de build
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=asset-builder /app/jslib ./jslib

# Ensure connect.inc.php fallback file is copied if missing and temp directory exists
RUN mkdir -p temp personnalisation \
    && cp -n ./personnalisation/connect.inc.php.docker ./personnalisation/connect.inc.php || true

CMD ["apache2-foreground"]
