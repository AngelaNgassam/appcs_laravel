# PHP 8.4 pour correspondre aux exigences du composer.json
FROM php:8.4-fpm

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip bcmath

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définit le dossier de travail dans le conteneur
WORKDIR /var/www

# Copie des fichiers du projet
COPY . .

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Création des répertoires nécessaires
RUN mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache

# Ajustement des permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Port PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
