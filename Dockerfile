# Étape 1 : Construction des ressources frontend
FROM node:krypton-alpine AS asset-builder
WORKDIR /app
COPY package.json package-lock.json ./
COPY build ./build
RUN npm ci --include=dev

# Étape 2 : Construction des dépendances PHP
FROM composer:2 AS composer-builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Étape 3 : Environnement d'exécution final
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

# Activation des modules Apache requis
RUN a2enmod rewrite headers deflate mime autoindex

# Configuration d'Apache pour autoriser les surcharges .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/conf-available/allow-override.conf \
    && a2enconf allow-override

WORKDIR /var/www/html

# Copie du code source de l'application
COPY . .

# Copie des dossiers vendor et jslib construits lors des étapes précédentes
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=asset-builder /app/jslib ./jslib

# Génération du fichier connect.inc.php lisant dynamiquement les variables d'environnement
RUN printf '<?php\n# Fichier de connexion à la base de données dynamique pour Docker\n# Lit les informations depuis les variables d\'environnement\n$dbHost = getenv("DB_HOST") ?: "db";\n$dbDb   = getenv("DB_NAME") ?: "grr";\n$dbUser = getenv("DB_USER") ?: "grr";\n$dbPass = getenv("DB_PASSWORD") ?: "grr_password";\n$dbPort = getenv("DB_PORT") ?: "3306";\n?>\n' > personnalisation/connect.inc.php

# Création du dossier temporaire et configuration des droits d'accès pour www-data
RUN mkdir -p temp \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/personnalisation /var/www/html/temp

CMD ["apache2-foreground"]
