#!/bin/bash
# Punta il traffico nginx sull'istanza green (app-green:9000)
set -e
APP_DIR="${APP_DIR:-/var/www/revenue.magellano.ai}"
UPSTREAM_CONF="$APP_DIR/docker/nginx/upstream.conf"
cat > "$UPSTREAM_CONF" <<'EOF'
upstream backend {
    server app-green:9000;
}
EOF
docker exec revenue_nginx nginx -s reload 2>/dev/null || true
echo "✓ Traffic switched to green"
