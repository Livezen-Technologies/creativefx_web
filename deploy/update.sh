#!/usr/bin/env bash
#
# Norlanka — update an existing deployment to the latest commit.
# Run as root:  bash deploy/update.sh   (from anywhere; uses APP_DIR)
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/norlankamfg}"
BRANCH="${BRANCH:-claude/awesome-planck-01cc95}"
WEB_USER="${WEB_USER:-www-data}"
PHP_BIN="${PHP_BIN:-php}"

[ "$(id -u)" -eq 0 ] || { echo "Run as root."; exit 1; }
[ -d "$APP_DIR/.git" ] || { echo "$APP_DIR is not a checkout — run setup.sh first."; exit 1; }

cd "$APP_DIR"
echo "==> Pulling $BRANCH"
git fetch --depth 1 origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> composer install"
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null; then
  echo "==> building assets"
  npm ci || npm install
  npm run build
fi

chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR"
chmod -R ug+rwX "$APP_DIR/writable"

echo "==> migrations"
sudo -u "$WEB_USER" "$PHP_BIN" spark migrate --all

# Reload php-fpm to clear opcache (best-effort).
systemctl reload "php$($PHP_BIN -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm" 2>/dev/null || true
echo "==> Done."
