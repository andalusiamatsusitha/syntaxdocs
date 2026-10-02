FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    bash \
    icu-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache \
    bcmath

# Set working directory to workspace root
WORKDIR /var/www/syntaxdocs

EXPOSE 9000

CMD ["php-fpm"]
