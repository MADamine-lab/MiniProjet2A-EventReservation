FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip openssl \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    libicu-dev libexif-dev g++ \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Set working directory
WORKDIR /var/www/html

# Copy all application files (vendor/ inclus depuis la machine hôte)
COPY . .

# Generate JWT keys
RUN mkdir -p config/jwt && \
    openssl genpkey -algorithm RSA \
        -out config/jwt/private.pem \
        -aes256 -pass pass:your_jwt_passphrase \
        -pkeyopt rsa_keygen_bits:4096 && \
    openssl pkey \
        -in config/jwt/private.pem \
        -out config/jwt/public.pem \
        -pubout -passin pass:your_jwt_passphrase

# Permissions
RUN mkdir -p var/cache var/log public/uploads/events \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 public \
    && chmod -R 777 var

EXPOSE 9000
CMD ["php-fpm"]