FROM php:8.4-fpm-bookworm

# Install system deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    zip \
    && docker-php-ext-install intl pdo_mysql zip

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

COPY . .

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
