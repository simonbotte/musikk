FROM node:24-alpine
WORKDIR /app
COPY . .
RUN apk add --no-cache \
    bash \
    curl \
    unzip \
    git \
    openssh-client \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    zlib-dev
RUN yarn install
EXPOSE 3000