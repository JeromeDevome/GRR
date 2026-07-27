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
FROM php:8.3-apache

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

# Copie du code source avec droits www-data (évite des couches d'images trop lourdes)
COPY --chown=www-data:www-data . .

# Copie des dépendances générées depuis les étapes de build
COPY --chown=www-data:www-data --from=composer-builder /app/vendor ./vendor
COPY --chown=www-data:www-data --from=asset-builder /app/jslib ./jslib

# Création du dossier temp et ajustement des droits pour www-data
RUN mkdir -p temp \
    && chown -R www-data:www-data /var/www/html/personnalisation /var/www/html/temp \
    && chmod -R 775 /var/www/html/personnalisation /var/www/html/temp

CMD ["apache2-foreground"]
