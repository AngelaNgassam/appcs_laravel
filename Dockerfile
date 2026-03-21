# PHP 8.4 pour Symfony 8 / Laravel (Pest 4)
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

# Definis le dossier de travail dans le conteneur
WORKDIR /var/www

# Copie des fichiers du projet
COPY . .

# Installation des dépendances PHP
# AJOUT : --no-scripts pour éviter que Laravel ne tente d'exécuter du code PHP avant que l'image soit prête
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Création des répertoires nécessaires
RUN mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/bootstrap/cache

# Ajustement des permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Dire a Docker sur quel port l'application tourne
EXPOSE 9000

# Commande qui lance l'applicatiom
CMD ["php-fpm"]
