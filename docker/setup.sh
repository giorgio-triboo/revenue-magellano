#!/bin/bash

set -e

echo "🚀 Setting up Laravel application in Docker..."

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
max_attempts=30
attempt=0
until docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Attempt $attempt/$max_attempts..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "❌ MySQL failed to start after $max_attempts attempts"
    exit 1
fi

echo "✅ MySQL is ready!"

# Install dependencies
echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader

# Install npm dependencies
echo "📦 Installing NPM dependencies..."
docker-compose exec -T app npm install

# Copy .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
fi

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec -T app php artisan key:generate

# Run migrations
echo "🗄️  Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Seed database (optional, uncomment if needed)
# echo "🌱 Seeding database..."
# docker-compose exec -T app php artisan db:seed

# Create storage link
echo "🔗 Creating storage link..."
docker-compose exec -T app php artisan storage:link

# Set permissions
echo "🔐 Setting permissions..."
docker-compose exec -T app sh -c "chmod -R 775 storage bootstrap/cache || true"
docker-compose exec -T app sh -c "chown -R www-data:www-data storage bootstrap/cache || true"

# Clear and cache config
echo "🧹 Clearing and caching configuration..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

echo "✅ Setup complete!"
echo ""
echo "🌐 Application is available at: http://localhost:8080"
echo "📊 MySQL is available at: localhost:3306"
echo "🔴 Redis is available at: localhost:6379"
echo ""
echo "To start the queue worker, run:"
echo "  docker-compose up queue"
