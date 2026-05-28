#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/opt/hezarrial}"
COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"
ENV_FILE="${ENV_FILE:-.env.production}"

cd "$APP_DIR"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Missing $ENV_FILE. Create it from deploy/production.env.example first." >&2
    exit 1
fi

if [[ -n "${APP_IMAGE:-}" ]]; then
    export APP_IMAGE
fi

compose() {
    docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

compose pull
compose up -d --remove-orphans
compose exec -T web php artisan migrate --force
compose exec -T web php artisan optimize
compose exec -T web php artisan queue:restart
compose ps
docker image prune -f --filter "until=168h"
