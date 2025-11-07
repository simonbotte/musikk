# Étape 1 : Build Symfony avec Composer
FROM composer:2.7 AS builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction
COPY . .

# Étape 2 : Runtime PHP (léger et rapide)
FROM php:8.2-cli

WORKDIR /app
COPY --from=builder /app /app

# Installer extensions PHP nécessaires à Symfony
RUN apt-get update && apt-get install -y \
  libicu-dev libzip-dev zip unzip git \
  && docker-php-ext-install intl pdo_mysql opcache zip

# Crée un utilisateur non-root
RUN useradd -ms /bin/bash symfony
USER symfony

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
