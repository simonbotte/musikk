FROM php:8.4-cli-alpine

RUN apk add --no-cache \
  bash curl unzip git openssh-client \
  icu-dev oniguruma-dev libzip-dev zlib-dev \
  && docker-php-ext-install intl pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copie TOUT le projet (y compris bin/console)
COPY . .

# Ensuite seulement, installer les dépendances
# RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
