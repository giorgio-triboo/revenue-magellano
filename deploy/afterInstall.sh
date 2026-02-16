#!/bin/bash
#
# After install per revenue.magellano.ai: deploy con Docker e blue-green (zero downtime).
# Come deploy_exemple: composer e npm sono nel Dockerfile, afterInstall solo build + up + switch + migrate.
# - CodeDeploy copia i file in RELEASE
# - .env da AWS Secrets Manager
# - docker build (immagine con codice, vendor, frontend) → copia public/ su host per nginx
# - Docker Compose: redis, app-blue, app-green, nginx (DB MySQL esterno via .env)
#
# Esecuzione locale (test): APP_DIR=$(pwd) LOCAL_DEPLOY=1 ./deploy/afterInstall.sh

set -e

RELEASE="${APP_DIR:-/home/ec2-user/revenue.magellano.ai}"
cd "$RELEASE"

# Verifica che CodeDeploy abbia copiato i file (fase Install). Se la dir è vuota, Install è fallita.
if [ ! -f "composer.json" ] || [ ! -f "Dockerfile" ]; then
    echo "ERROR: $RELEASE is empty or incomplete (missing composer.json/Dockerfile)."
    echo "CodeDeploy Install phase may have failed. Check deployment logs for the Install step."
    echo "Contents of $RELEASE:"
    ls -la "$RELEASE" 2>/dev/null || true
    exit 1
fi

LOCAL_DEPLOY="${LOCAL_DEPLOY:-false}"
if [ "$LOCAL_DEPLOY" = "1" ] || [ "$LOCAL_DEPLOY" = "true" ]; then
    DOCKER_CMD="docker"
    COMPOSE_CMD="docker compose"
else
    DOCKER_CMD="docker"
    COMPOSE_CMD="docker compose"
fi

COMPOSE_FILES="-f docker-compose.yml -f docker-compose.bluegreen.yml"

echo "=========================================="
echo "DEPLOYMENT (Docker + Blue-Green)"
echo "=========================================="
echo "APP_DIR=$RELEASE"
echo ""

# --- .env da Secrets Manager (solo su server, non in LOCAL_DEPLOY) ---
if [ "$LOCAL_DEPLOY" != "1" ] && [ "$LOCAL_DEPLOY" != "true" ]; then
    echo "Copying .env from Secrets Manager..."
    aws secretsmanager get-secret-value \
        --secret-id "revenue/prod" \
        --query SecretString \
        --version-stage AWSCURRENT \
        --region eu-west-1 \
        --output text | \
        jq -r 'to_entries|map("\(.key)=\"\(.value|tostring)\"")|.[]' > "${RELEASE}/.env" || {
            echo "ERROR creating .env from secret"
            exit 1
        }
fi

# --- Directory e permessi Laravel ---
# ec2-user per il resto; storage e bootstrap/cache a uid 33 (www-data nel container) così PHP può scrivere view/cache
mkdir -p "${RELEASE}/storage/logs" "${RELEASE}/storage/framework/cache" "${RELEASE}/storage/framework/sessions" "${RELEASE}/storage/framework/views" "${RELEASE}/bootstrap/cache"
chown -R ec2-user:ec2-user "$RELEASE" 2>/dev/null || true
chown -R 33:33 "${RELEASE}/storage" "${RELEASE}/bootstrap/cache" 2>/dev/null || true
chmod -R 775 "${RELEASE}/storage" "${RELEASE}/bootstrap/cache" 2>/dev/null || true

# --- Docker Compose ---
if command -v docker-compose &> /dev/null; then
    DCOMPOSE="docker-compose"
elif docker compose version &> /dev/null; then
    DCOMPOSE="docker compose"
else
    echo "ERROR: Docker Compose not found"
    exit 1
fi

# --- Pulizia container, volumi orfani e risorse dangling (NON image prune -af: azzererebbe
#     la cache e ogni deploy rifarebbe build + node + composer/npm da zero, +10min).
#     volume prune rimuove solo volumi non usati (redis_data resta al sicuro). ---
echo "Cleaning up Docker containers, unused volumes and dangling resources..."
$DOCKER_CMD container prune -f 2>/dev/null || true
# Rimuovi container orfani con immagine revenue-app che non sono blue/green (es. vecchi run o deploy falliti)
for cid in $($DOCKER_CMD ps -q --filter "ancestor=revenue-app:latest" 2>/dev/null); do
    name="$($DOCKER_CMD inspect --format '{{.Name}}' "$cid" 2>/dev/null)"
    if [ "$name" != "/revenue_app_blue" ] && [ "$name" != "/revenue_app_green" ]; then
        echo "  Removing orphan container $cid ($name)"
        $DOCKER_CMD rm -f "$cid" 2>/dev/null || true
    fi
done
$DOCKER_CMD volume prune -f 2>/dev/null || true
$DOCKER_CMD image prune -f 2>/dev/null || true
$DOCKER_CMD builder prune -f 2>/dev/null || true

# --- Build immagine app (composer + npm già nel Dockerfile, niente step separati sul server) ---
echo "Building Docker image (revenue-app:latest)..."
$DOCKER_CMD build --network=host -t revenue-app:latest -f Dockerfile .

# --- Copia public/ dall'immagine all'host così nginx può servire statici (css, js, build) ---
mkdir -p "${RELEASE}/public"
echo "Copying public/ from image to host for nginx..."
$DOCKER_CMD run --rm -v "${RELEASE}:/out" revenue-app:latest sh -c "cp -a /var/www/html/public/. /out/public/"

# --- Avvio stack (redis, app-blue, app-green, nginx; DB esterno) ---
echo "Starting containers..."
$DCOMPOSE $COMPOSE_FILES up -d

# --- Chi è attivo ora? (default: blue) ---
if [ -f "${RELEASE}/docker/nginx/upstream.conf" ] && grep -q "app-green:9000" "${RELEASE}/docker/nginx/upstream.conf"; then
    CURRENT_COLOR="green"
    INACTIVE_COLOR="blue"
else
    CURRENT_COLOR="blue"
    INACTIVE_COLOR="green"
fi
echo "  Current traffic: $CURRENT_COLOR → deploying to $INACTIVE_COLOR"

# --- Riavvia il lato inattivo con il nuovo codice ---
$DCOMPOSE $COMPOSE_FILES up -d --force-recreate "app-$INACTIVE_COLOR"
TARGET_CONTAINER="revenue_app_$INACTIVE_COLOR"

# --- Attendi che il container sia healthy ---
echo "Waiting for container $TARGET_CONTAINER..."
MAX_WAIT=90
WAIT_COUNT=0
HEALTH_STATUS="starting"
while [ $WAIT_COUNT -lt $MAX_WAIT ]; do
    HEALTH_STATUS=$($DOCKER_CMD inspect --format='{{.State.Health.Status}}' "$TARGET_CONTAINER" 2>/dev/null || echo "starting")
    if [ "$HEALTH_STATUS" = "healthy" ]; then
        echo "  ✓ Container is healthy"
        break
    fi
    echo "  Waiting... ($WAIT_COUNT/$MAX_WAIT s)"
    sleep 5
    WAIT_COUNT=$((WAIT_COUNT + 5))
done

if [ "$HEALTH_STATUS" != "healthy" ]; then
    echo "ERROR: Container did not become healthy"
    $DCOMPOSE $COMPOSE_FILES ps
    exit 1
fi

# --- Switch traffico sul lato appena deployato ---
echo "Switching traffic to $INACTIVE_COLOR..."
APP_DIR="$RELEASE" "${RELEASE}/scripts/switch-to-${INACTIVE_COLOR}.sh"

# --- Migrazioni e artisan sul container che ora riceve il traffico (un solo exec) ---
MIGRATE_CONTAINER="$TARGET_CONTAINER"
echo "Running migrations and artisan on $MIGRATE_CONTAINER..."
$DOCKER_CMD exec "$MIGRATE_CONTAINER" sh -c "php artisan migrate --force && php artisan cache:clear && php artisan auth:clear-resets && php artisan config:clear && php artisan config:cache && php artisan optimize"

# --- Pulizia ---
echo "Removing .git folder..."
rm -rf "${RELEASE}/.git"

# --- Systemd: avvio stack al boot (solo su server) ---
if [ "$LOCAL_DEPLOY" != "1" ] && [ "$LOCAL_DEPLOY" != "true" ] && [ -f "${RELEASE}/scripts/revenue-docker.service" ]; then
    echo "Installing systemd service..."
    cp "${RELEASE}/scripts/revenue-docker.service" /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable revenue-docker.service
    echo "  ✓ revenue-docker.service enabled"
fi

echo ""
echo "=========================================="
echo "✓ DEPLOYMENT COMPLETED (Blue-Green)"
echo "=========================================="
echo "  • Traffic on: $INACTIVE_COLOR"
echo "  • Nginx listening on port 80"
echo "  • Containers: redis, app-blue, app-green, nginx (DB esterno)"
$DCOMPOSE $COMPOSE_FILES ps
echo ""
