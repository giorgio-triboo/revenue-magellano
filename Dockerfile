# Production: codice, composer e frontend nell'immagine (come deploy_exemple).
# Build context: repo root. Sul server: docker build da RELEASE.
# Niente composer/npm in afterInstall: tutto qui.

FROM php:8.4-fpm-bookworm

WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Node per build frontend (stesso approccio di deploy_exemple)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia codice (vendor e node_modules esclusi da .dockerignore)
COPY . .

# Dipendenze PHP e frontend nell'immagine
RUN composer install --no-dev --no-interaction --optimize-autoloader \
    && npm ci && npm run build \
    && rm -rf node_modules

# Directory scrivibili Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
