#!/usr/bin/env bash
# Despliega la última versión de un proyecto.
# Uso: sudo -u www-data -H deploy-site <nombre> [rama]
set -euo pipefail

NAME=$1; BRANCH=${2:-main}
cd /var/www/$NAME

php artisan down --retry=15 || true
trap 'php artisan up' EXIT

git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"

composer install --no-dev --optimize-autoloader --no-interaction
if [ -f package.json ]; then npm ci --no-audit --no-fund && npm run build; fi

php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan optimize
php artisan filament:optimize 2>/dev/null || true
php artisan queue:restart

echo "Deploy de $NAME listo."
