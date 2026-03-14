FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Generate JWT keys
RUN mkdir -p config/jwt && \
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:your_jwt_passphrase -pkeyopt rsa_keygen_bits:4096 && \
    openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_jwt_passphrase

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/public

EXPOSE 9000

CMD ["php-fpm"]
