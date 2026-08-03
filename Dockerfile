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

# --- Étape 3 : Compilation des extensions PHP (headers -dev isolés du runtime final) ---
FROM php:8.5-apache AS php-extensions-builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    libldap-dev \
    libsasl2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/$(dpkg-architecture -qDEB_HOST_MULTIARCH) \
    && docker-php-ext-install -j$(nproc) \
    gd \
    intl \
    ldap \
    mysqli \
    zip

# --- Étape 4 : Environnement d'exécution final ---
FROM php:8.5-apache

# Bibliothèques runtime
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng16-16t64 \
    libjpeg62-turbo \
    libfreetype6 \
    libicu76 \
    libzip5 \
    libldap2 \
    libsasl2-2 \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Récupération des extensions PHP compilées dans l'étape dédiée
COPY --from=php-extensions-builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-extensions-builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

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

# Config de connexion par défaut via variables d'environnement, tant que personnalisation/connect.inc.php n'existe pas
RUN cp include/connect.inc.docker.php include/connect.inc.php

# temp/ n'est pas versionné (.dockerignore) : le créer avec les droits www-data
RUN mkdir -p temp && chown www-data:www-data temp

CMD ["apache2-foreground"]
