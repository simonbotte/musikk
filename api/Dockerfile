FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    unzip \
    git \
    openssh-client \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    zlib-dev \
  && docker-php-ext-install intl pdo_mysql zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html
COPY . .

EXPOSE 8000