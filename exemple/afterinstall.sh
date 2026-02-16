#!/bin/bash

# After install script for agente-magellano-ai application
#
# PROCESSO DI DEPLOYMENT:
# 1. CodeDeploy copia i nuovi file sorgente sul server
# 2. App: build con cache Docker. Se cambi solo .ts → riusa npm install, rifà solo Next.js build.
#    Rebuild completo (npm install + build) solo se cambi package.json o Dockerfile.
# 3. Database: preservato di default; opzionale reset con RESET_DATABASE=true
# 4. Avvia i container (postgres e app; con blue-green: app-blue, app-green, nginx)
# 5. Esegue migrazioni (incrementali o full+seed se reset DB)
# 6. Pulizia: immagini/container/volumi vecchi + build cache non usata (evita riempimento disco)
#
# BLUE-GREEN (zero downtime): se BLUE_GREEN=true o esiste docker-compose.bluegreen.yml
# - Due istanze app (blue/green), nginx sulla porta 3000. Deploy sulla istanza inattiva,
#   poi switch traffico (nessun downtime).
# - Build su app-blue; riavvio del lato inattivo; migrazioni sul lato inattivo; switch.
#
# ESECUZIONE LOCALE (validare il deploy prima del server):
#   APP_DIR=$(pwd) LOCAL_DEPLOY=1 ./afterinstall.sh
# Oppure: ./scripts/run-afterinstall-local.sh
#
# COMPORTAMENTO DEFAULT: Preservazione database (RESET_DATABASE=false)
# Per reset completo del database:
#   sudo RESET_DATABASE=true ./afterinstall.sh
# ATTENZIONE: Con RESET_DATABASE=true vengono cancellati TUTTI i dati nel database!

set -e

echo "Starting afterinstall script..."

# Esecuzione locale: APP_DIR=cwd, nessun sudo. Su server: APP_DIR=/home/ec2-user/agente-magellano-ai
APP_DIR="${APP_DIR:-/home/ec2-user/agente-magellano-ai}"
LOCAL_DEPLOY="${LOCAL_DEPLOY:-false}"
cd "$APP_DIR"

# Blue-green: attivo se BLUE_GREEN=true o se esiste docker-compose.bluegreen.yml
if [ -n "$BLUE_GREEN" ]; then
    USE_BLUE_GREEN="$BLUE_GREEN"
else
    USE_BLUE_GREEN="false"
    [ -f "$APP_DIR/docker-compose.bluegreen.yml" ] && USE_BLUE_GREEN="true"
fi

# Comando Docker Compose e prefisso (sudo -u ec2-user su server, vuoto in locale)
if [ "$LOCAL_DEPLOY" = "1" ] || [ "$LOCAL_DEPLOY" = "true" ]; then
    DOCKER_RUN=""
else
    DOCKER_RUN="sudo -u ec2-user"
fi

# Crea directory logs se non esiste e sistema permessi
mkdir -p "$APP_DIR/logs"
chown -R 1001:1001 "$APP_DIR/logs" 2>/dev/null || chmod 777 "$APP_DIR/logs"
chmod 775 "$APP_DIR/logs"

# Verifica Docker Compose
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE_CMD="docker-compose"
elif docker compose version &> /dev/null; then
    DOCKER_COMPOSE_CMD="docker compose"
else
    echo "ERROR: Docker Compose not found"
    exit 1
fi

# File compose: uno solo (produzione classica) o due (blue-green)
if [ "$USE_BLUE_GREEN" = "true" ]; then
    COMPOSE_FILES="-f docker-compose.yml -f docker-compose.bluegreen.yml"
    echo "✓ Blue-green deploy attivo (zero downtime)"
else
    COMPOSE_FILES="-f docker-compose.yml"
fi

# Controlla se rimuovere il database (variabile d'ambiente RESET_DATABASE)
RESET_DATABASE="${RESET_DATABASE:-false}"

# Comandi con eventuale sudo -u ec2-user (vuoti in LOCAL_DEPLOY)
[ -n "$DOCKER_RUN" ] && COMPOSE_CMD="$DOCKER_RUN $DOCKER_COMPOSE_CMD" || COMPOSE_CMD="$DOCKER_COMPOSE_CMD"
[ -n "$DOCKER_RUN" ] && DOCKER_CMD="$DOCKER_RUN docker" || DOCKER_CMD="docker"

echo "=========================================="
echo "DEPLOYMENT"
echo "=========================================="
if [ "$RESET_DATABASE" = "true" ]; then
    echo "🔄 RESET DATABASE: volume verrà RIMOSSO e RICREATO (migrate:all + seed)"
    echo "   (RESET_DATABASE=true)"
else
    echo "✓ MODO NORMALE: Database preservato, app sempre ricostruita da zero"
    echo "   (RESET_DATABASE=false)"
fi
echo "=========================================="

# Se RESET_DATABASE=true, ferma tutti i container e rimuovi il volume
if [ "$RESET_DATABASE" = "true" ]; then
    echo ""
    echo "🔄 RESET DATABASE MODE ATTIVATO"
    echo "=========================================="
    
    echo "Stopping all containers..."
    $COMPOSE_CMD $COMPOSE_FILES down || true
    
    echo "Removing database volume..."
    if docker volume ls -q | grep -q "^magellano_postgres_data_prod$"; then
        docker volume rm magellano_postgres_data_prod || {
            echo "⚠️  Warning: Could not remove volume (might be in use)"
            docker volume rm -f magellano_postgres_data_prod 2>/dev/null || true
        }
        echo "✅ Database volume removed"
    else
        echo "ℹ️  Database volume not found (already removed or never created)"
    fi
    echo "=========================================="
    echo ""
else
    # Senza blue-green: ferma solo il container app (magellano-ai)
    # Con blue-green: non fermare i container live; si deploya sul lato inattivo
    if [ "$USE_BLUE_GREEN" != "true" ]; then
        if $DOCKER_CMD ps -a --format "{{.Names}}" 2>/dev/null | grep -q "magellano-ai$"; then
            echo "Stopping existing application container..."
            $COMPOSE_CMD $COMPOSE_FILES stop app || true
            $COMPOSE_CMD $COMPOSE_FILES rm -f app || true
        fi
    fi
    
    # Assicurati che postgres sia in esecuzione
    if ! $DOCKER_CMD ps --format "{{.Names}}" 2>/dev/null | grep -q "magellano-postgres-prod"; then
        echo "Postgres container not running, starting it..."
        $COMPOSE_CMD $COMPOSE_FILES up -d postgres
        
        echo "Waiting for postgres to be ready..."
        MAX_WAIT_POSTGRES=60
        WAIT_COUNT_POSTGRES=0
        while [ $WAIT_COUNT_POSTGRES -lt $MAX_WAIT_POSTGRES ]; do
            if $DOCKER_CMD exec magellano-postgres-prod pg_isready -U magellano >/dev/null 2>&1; then
                echo "  ✓ Postgres is ready!"
                break
            fi
            echo "    Waiting for postgres... ($WAIT_COUNT_POSTGRES/$MAX_WAIT_POSTGRES seconds)"
            sleep 2
            WAIT_COUNT_POSTGRES=$((WAIT_COUNT_POSTGRES + 2))
        done
        
        if [ $WAIT_COUNT_POSTGRES -ge $MAX_WAIT_POSTGRES ]; then
            echo "⚠️  Warning: Postgres might not be fully ready, but continuing..."
        fi
    fi
fi

# Pulizia: rimuovere container/immagini vecchie e build cache non usata
echo "Cleaning up Docker resources (containers, old images, unused build cache)..."
echo "Disk usage before cleanup:"
$DOCKER_CMD system df 2>/dev/null || true

echo "Removing unused build cache (keeps cache used by last build, frees old layers)..."
$DOCKER_CMD builder prune -f 2>/dev/null || true

echo "Removing stopped containers..."
$DOCKER_CMD container prune -f 2>/dev/null || true

echo "Removing old app image versions..."
for img_id in $($DOCKER_CMD images -q agente-magellano-ai_app 2>/dev/null); do
    $DOCKER_CMD rmi -f "$img_id" 2>/dev/null || true
done
for img_id in $($DOCKER_CMD images -q agente-magellano-ai-app 2>/dev/null); do
    $DOCKER_CMD rmi -f "$img_id" 2>/dev/null || true
done

echo "Removing unused Docker images..."
$DOCKER_CMD image prune -af 2>/dev/null || true

$DOCKER_CMD network prune -f 2>/dev/null || true

echo "Removing unused volumes (preserving database volume when not reset)..."
if [ "$RESET_DATABASE" = "true" ]; then
    PROTECTED_VOLUMES="magellano_postgres_data_dev magellano_node_modules_dev"
else
    PROTECTED_VOLUMES="magellano_postgres_data_prod magellano_postgres_data_dev magellano_node_modules_dev"
fi
$DOCKER_CMD volume ls -q 2>/dev/null | while read vol; do
    if ! echo "$PROTECTED_VOLUMES" | grep -q "$vol"; then
        $DOCKER_CMD volume rm "$vol" 2>/dev/null || true
    fi
done
$DOCKER_CMD volume prune -f 2>/dev/null || true

# Build: con blue-green si builda sempre app-blue (produce magellano-app:latest)
# Senza blue-green si builda app
echo "Building Docker image (using cache when only source changed)..."
if [ "$USE_BLUE_GREEN" = "true" ]; then
    $COMPOSE_CMD $COMPOSE_FILES build app-blue
else
    $COMPOSE_CMD $COMPOSE_FILES build app
fi

# Avvio container
echo "Starting containers..."
if [ "$RESET_DATABASE" = "true" ]; then
    echo "  Starting postgres (will create new volume)..."
    $COMPOSE_CMD $COMPOSE_FILES up -d postgres
    
    echo "  Waiting for postgres to be ready..."
    MAX_WAIT_POSTGRES=60
    WAIT_COUNT_POSTGRES=0
    while [ $WAIT_COUNT_POSTGRES -lt $MAX_WAIT_POSTGRES ]; do
        if $DOCKER_CMD exec magellano-postgres-prod pg_isready -U magellano >/dev/null 2>&1; then
            echo "  ✓ Postgres is ready!"
            break
        fi
        echo "    Waiting for postgres... ($WAIT_COUNT_POSTGRES/$MAX_WAIT_POSTGRES seconds)"
        sleep 2
        WAIT_COUNT_POSTGRES=$((WAIT_COUNT_POSTGRES + 2))
    done
    
    if [ $WAIT_COUNT_POSTGRES -ge $MAX_WAIT_POSTGRES ]; then
        echo "⚠️  Warning: Postgres might not be fully ready, but continuing..."
    fi
fi

if [ "$USE_BLUE_GREEN" = "true" ]; then
    # Blue-green: avvia tutto (postgres già su se non reset), poi deploy sul lato inattivo
    $COMPOSE_CMD $COMPOSE_FILES up -d
    # Chi è attivo ora? Leggiamo da upstream.conf (default: blue)
    if [ -f "$APP_DIR/nginx/upstream.conf" ] && grep -q "app-green:3000" "$APP_DIR/nginx/upstream.conf"; then
        CURRENT_COLOR="green"
        INACTIVE_COLOR="blue"
    else
        CURRENT_COLOR="blue"
        INACTIVE_COLOR="green"
    fi
    echo "  Traffico attuale: $CURRENT_COLOR → deploy sul lato $INACTIVE_COLOR"
    # Riavvia il lato inattivo con la nuova immagine
    $COMPOSE_CMD $COMPOSE_FILES up -d --force-recreate "app-$INACTIVE_COLOR"
    TARGET_CONTAINER="magellano-ai-$INACTIVE_COLOR"
else
    echo "Starting application container..."
    $COMPOSE_CMD $COMPOSE_FILES up -d --force-recreate app
    TARGET_CONTAINER="magellano-ai"
fi

# Attendi che il container target sia healthy
echo "Waiting for container to be healthy ($TARGET_CONTAINER)..."
MAX_WAIT=120
WAIT_COUNT=0
HEALTH_STATUS="starting"
while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
    HEALTH_STATUS=$($DOCKER_CMD inspect --format='{{.State.Health.Status}}' "$TARGET_CONTAINER" 2>/dev/null || echo "starting")
    if [ "$HEALTH_STATUS" = "healthy" ]; then
        echo "✓ Container is healthy!"
        break
    fi
    echo "  Waiting... ($WAIT_COUNT/$MAX_WAIT seconds)"
    sleep 5
    WAIT_COUNT=$((WAIT_COUNT + 5))
done

if [ "$HEALTH_STATUS" != "healthy" ]; then
    echo "ERROR: Container failed to become healthy"
    $COMPOSE_CMD $COMPOSE_FILES ps
    exit 1
fi

# Blue-green: switch traffico sul lato appena deployato
if [ "$USE_BLUE_GREEN" = "true" ]; then
    echo "Switching traffic to $INACTIVE_COLOR..."
    "$APP_DIR/scripts/switch-to-$INACTIVE_COLOR.sh"
fi

# Pulizia finale (dopo build: container, immagini, build cache non usata dall'ultimo build)
echo "Final cleanup: removing old containers, images, and unused build cache..."
$DOCKER_CMD container prune -f 2>/dev/null || true
$DOCKER_CMD image prune -af 2>/dev/null || true
$DOCKER_CMD builder prune -f 2>/dev/null || true

echo ""
echo "Docker disk usage after cleanup:"
$DOCKER_CMD system df
echo ""

# Verifica che il container target sia in esecuzione
if $DOCKER_CMD ps --format "{{.Names}}" | grep -q "^${TARGET_CONTAINER}$"; then
    echo "✓ Deployment successful"
    $COMPOSE_CMD $COMPOSE_FILES ps
    
    # Esegui migrazioni sul container attivo (quello che ora riceve traffico)
    MIGRATE_CONTAINER="$TARGET_CONTAINER"
    echo "Running database migrations (on $MIGRATE_CONTAINER)..."
    if [ "$RESET_DATABASE" = "true" ]; then
        echo "  Volume ricreato: sempre migrate:all (schema + seed)"
        sleep 3
        
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run migrate:all || {
          echo "⚠️  Migration/seed failed"
          exit 1
        }
        echo ""
        echo "  ✅ Database reset completed with schema, metrics, aliases, and initial super-admin"
        
        echo ""
        echo "  📝 Populating metric aliases..."
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run db:populate-aliases || true
        
        echo ""
        echo "  📈 Analyzing database statistics..."
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run db:analyze-stats || true
    else
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run migrate:base || {
          echo "⚠️  Migration failed or already completed"
        }
        
        # Seed metriche (idempotente): popola dim_metric se vuota o aggiorna
        # Senza questo, db:populate-aliases non trova metriche e aggiunge 0 alias
        echo ""
        echo "Seeding metriche (idempotent)..."
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run db:seed-metrics || {
          echo "⚠️  Seed metriche failed (non critico, continua)"
        }
        
        echo ""
        echo "Populating metric aliases (idempotent)..."
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run db:populate-aliases || true
        
        echo ""
        echo "Analyzing database statistics..."
        $DOCKER_CMD exec "$MIGRATE_CONTAINER" npm run db:analyze-stats || true
    fi
else
    echo "ERROR: Container is not running"
    $COMPOSE_CMD $COMPOSE_FILES ps
    exit 1
fi

# Installa/aggiorna servizio systemd per avviare i container al boot (solo su server, non in LOCAL_DEPLOY)
if [ "$LOCAL_DEPLOY" != "1" ] && [ "$LOCAL_DEPLOY" != "true" ] && [ -f "$APP_DIR/scripts/magellano-docker.service" ]; then
    echo "Installing systemd service for Docker stack (boot + manual start)..."
    cp "$APP_DIR/scripts/magellano-docker.service" /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable magellano-docker.service
    echo "  ✓ magellano-docker.service enabled (containers will start on boot)"
fi

echo ""
echo "=========================================="
echo "✓ DEPLOYMENT COMPLETED"
echo "=========================================="
if [ "$USE_BLUE_GREEN" = "true" ]; then
    echo "  • Blue-green: traffico su $INACTIVE_COLOR (zero downtime)"
fi
echo "  • Application container(s) rebuilt"
if [ "$RESET_DATABASE" = "true" ]; then
    echo "  • Database volume RESET and recreated"
else
    echo "  • Database volume preserved"
fi
echo "  • Old Docker images cleaned"
if [ "$LOCAL_DEPLOY" != "1" ] && [ "$LOCAL_DEPLOY" != "true" ]; then
    echo "  • Systemd service enabled (docker compose up on boot)"
fi