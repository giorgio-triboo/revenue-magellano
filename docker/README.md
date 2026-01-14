# Docker Setup per Revenue Platform

Questo setup Docker fornisce un ambiente di sviluppo locale completo per testare le modifiche prima di metterle in produzione.

## Servizi inclusi

- **app**: Container PHP 8.4-FPM con tutte le estensioni necessarie
- **nginx**: Server web Nginx
- **mysql**: Database MySQL 8.0
- **redis**: Cache Redis

**Nota**: Il queue worker può essere avviato manualmente quando necessario nel container app.

## Prerequisiti

- Docker Desktop installato e avviato
- Docker Compose v2 o superiore

## Setup iniziale

1. **Avvia i container:**
   ```bash
   docker-compose up -d
   ```

2. **Esegui lo script di setup:**
   ```bash
   chmod +x docker/setup.sh
   ./docker/setup.sh
   ```

   Oppure manualmente:
   ```bash
   # Installa dipendenze
   docker-compose exec app composer install
   docker-compose exec app npm install
   
   # Configura .env (se non esiste)
   cp .env.example .env
   docker-compose exec app php artisan key:generate
   
   # Esegui migrazioni
   docker-compose exec app php artisan migrate
   
   # Crea storage link
   docker-compose exec app php artisan storage:link
   ```

## Comandi utili

### Avviare i container
```bash
docker-compose up -d
```

### Fermare i container
```bash
docker-compose down
```

### Visualizzare i log
```bash
# Tutti i servizi
docker-compose logs -f

# Solo app
docker-compose logs -f app

# Solo queue
docker-compose logs -f queue
```

### Eseguire comandi Artisan
```bash
docker-compose exec app php artisan [comando]
```

### Accedere al container
```bash
docker-compose exec app bash
```

### Eseguire migrazioni
```bash
docker-compose exec app php artisan migrate
```

### Eseguire seeders
```bash
docker-compose exec app php artisan db:seed
```

### Avviare il queue worker
```bash
# Avvia il queue worker nel container app esistente
docker-compose exec -d app php artisan queue:work --tries=3 --timeout=3600

# Oppure usa il Makefile
make queue
```

### Pulire cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Rebuild dei container
```bash
docker-compose build --no-cache
docker-compose up -d
```

## Configurazione .env

Assicurati che il file `.env` contenga queste configurazioni:

```env
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=revenue
DB_USERNAME=revenue
DB_PASSWORD=revenue

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=database
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

## Porte

- **8080**: Applicazione web (http://localhost:8080)
- **3306**: MySQL
- **6379**: Redis

## Volumi

I dati vengono persistiti in volumi Docker:
- `mysql_data`: Database MySQL
- `redis_data`: Dati Redis

Per eliminare tutti i dati:
```bash
docker-compose down -v
```

## Troubleshooting

### Problemi con i permessi
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Problemi con Composer
```bash
docker-compose exec app composer clear-cache
docker-compose exec app composer install --no-interaction
```

### Reset completo
```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
./docker/setup.sh
```
