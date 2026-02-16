# Alpine 3.19 esplicita: repo community e pacchetti stabili (libzip-dev, zip, unzip, oniguruma-dev)
FROM php:8.4-fpm-alpine3.19

# Set working directory
WORKDIR /var/www/html

# Abilita repo community (libzip-dev, zip, unzip) e aggiorna indice (retry per errori di rete)
RUN sed -i '/community/s/^# *//' /etc/apk/repositories 2>/dev/null || true \
    && (grep -q community /etc/apk/repositories || ( \
        MAIN=$(grep -oE 'v[0-9]+\.[0-9]+' /etc/apk/repositories | head -1) && \
        MAIN=${MAIN:-v3.19} && \
        echo "https://dl-cdn.alpinelinux.org/alpine/${MAIN}/community" >> /etc/apk/repositories )) \
    && for i in 1 2 3; do apk update && break || sleep 10; done

# Install system dependencies (Alpine: apk)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip opcache \
    && apk del --no-cache libpng-dev libxml2-dev libzip-dev freetype-dev libjpeg-turbo-dev oniguruma-dev \
    && rm -rf /tmp/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set permissions for www-data user
RUN chown -R www-data:www-data /var/www/html

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
