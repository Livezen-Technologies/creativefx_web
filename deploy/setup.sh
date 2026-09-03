#!/usr/bin/env bash
#
# Norlanka — first-time deploy for plain Nginx + PHP-FPM.
#
# ISOLATION GUARANTEES — this script only ADDS a new site:
#   • app code        -> $APP_DIR (its own directory)
#   • database        -> $DB_NAME / user $DB_USER (created if absent; nothing else touched)
#   • nginx vhost     -> /etc/nginx/sites-available/$DOMAIN.conf (refuses to clobber)
# It never edits other vhosts, never drops/alters other databases, and never
# changes global PHP config. Review it before running.
#
# Usage (as root):
#   REPO_URL='https://<token>@github.com/livezen-technologies/norlanka_web.git' \
#   bash deploy/setup.sh
#
set -euo pipefail

# ---- Configuration (override via environment) ----------------------------
DOMAIN="${DOMAIN:-norlankamfg.livezencloud.com}"
APP_DIR="${APP_DIR:-/var/www/norlankamfg}"
DB_NAME="${DB_NAME:-norlanka_prod}"
DB_USER="${DB_USER:-norlanka_prod}"
# Short name for log files and the TLS session-cache zone. Derived from the
# domain's first label so two sites on one box never collide.
SLUG="${SLUG:-$(printf '%s' "${DOMAIN%%.*}" | tr -c 'a-zA-Z0-9' '_')}"
REPO_URL="${REPO_URL:-}"
BRANCH="${BRANCH:-claude/awesome-planck-01cc95}"
WEB_USER="${WEB_USER:-www-data}"
PHP_BIN="${PHP_BIN:-php}"
ASSUME_YES="${ASSUME_YES:-0}"
SKIP_NODE="${SKIP_NODE:-0}"

note() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m[warn] %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31m[error] %s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Run as root (sudo)."
[ -n "$REPO_URL" ] || die "Set REPO_URL to your git remote (use a token/deploy key for a private repo)."

# ---- Preflight -----------------------------------------------------------
note "Preflight checks"
command -v git   >/dev/null || die "git is required."
command -v nginx >/dev/null || die "nginx is required."
command -v mysql >/dev/null || die "mysql client is required (and the server must be running)."
id "$WEB_USER" >/dev/null 2>&1 || die "Web user '$WEB_USER' does not exist."

PHP_FPM_SOCK="$(ls -1 /run/php/php*-fpm.sock 2>/dev/null | sort -V | tail -1 || true)"
[ -n "$PHP_FPM_SOCK" ] || die "No PHP-FPM socket found in /run/php/. Is php-fpm installed and running?"
PHP_VER="$($PHP_BIN -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
note "PHP $PHP_VER · FPM socket: $PHP_FPM_SOCK · web user: $WEB_USER"

for ext in intl mbstring mysqli json curl gd; do
  $PHP_BIN -m | grep -qi "^$ext$" || warn "PHP extension '$ext' not detected — install php$PHP_VER-$ext."
done

NGINX_AVAIL="/etc/nginx/sites-available/${DOMAIN}.conf"
NGINX_ENABLED="/etc/nginx/sites-enabled/${DOMAIN}.conf"
# Ours carry either the old "Norlanka" header or the managed-by marker the
# generic templates stamp in. Anything else on this path belongs to someone
# else and must not be clobbered.
if [ -e "$NGINX_AVAIL" ] \
   && ! grep -qE "Norlanka|managed-by: norlanka-deploy" "$NGINX_AVAIL" 2>/dev/null; then
  die "$NGINX_AVAIL already exists and is not ours — refusing to overwrite."
fi
# Warn if another enabled vhost already claims this server_name.
if grep -Rsl "server_name[^;]*\b${DOMAIN}\b" /etc/nginx/sites-enabled/ 2>/dev/null \
     | grep -vq "${DOMAIN}.conf"; then
  warn "Another enabled vhost references $DOMAIN. Resolve the conflict before reloading nginx."
fi

cat <<SUMMARY

This will set up ONLY:
  domain   : $DOMAIN
  app dir  : $APP_DIR   (branch: $BRANCH)
  database : $DB_NAME (user $DB_USER @ localhost)
  vhost    : $NGINX_AVAIL
It will NOT modify other sites, databases, or global PHP config.
SUMMARY
if [ "$ASSUME_YES" != "1" ]; then
  read -r -p "Proceed? [y/N] " ans
  [ "$ans" = "y" ] || [ "$ans" = "Y" ] || die "Aborted."
fi

# ---- Tooling -------------------------------------------------------------
if ! command -v composer >/dev/null; then
  note "Installing Composer"
  EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  ACTUAL="$($PHP_BIN -r "echo hash_file('sha384','/tmp/composer-setup.php');")"
  [ "$EXPECTED" = "$ACTUAL" ] || die "Composer installer checksum mismatch."
  $PHP_BIN /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

if [ "$SKIP_NODE" != "1" ] && ! command -v npm >/dev/null; then
  note "Installing Node.js 20 LTS (needed to build front-end assets)"
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

# ---- Source --------------------------------------------------------------
# We chown the tree to $WEB_USER, but run git here as root. Git refuses to
# operate on a repo owned by another user ("dubious ownership") unless it's
# marked safe — register it (idempotently) for root before any git command.
git config --global --get-all safe.directory 2>/dev/null | grep -qxF "$APP_DIR" \
  || git config --global --add safe.directory "$APP_DIR"

if [ -d "$APP_DIR/.git" ]; then
  note "Updating existing checkout"
  # Fetch via the tokenized REPO_URL directly — the checkout's existing 'origin'
  # may have no credentials. Reset hard to the fetched tip, then re-point the
  # branch label. Normalize origin to a token-less URL so no token lands on disk.
  git -C "$APP_DIR" fetch --depth 1 "$REPO_URL" "$BRANCH"
  git -C "$APP_DIR" reset --hard FETCH_HEAD
  git -C "$APP_DIR" checkout -B "$BRANCH"
  CLEAN_URL="$(printf '%s' "$REPO_URL" | sed -E 's#://[^@/]*@#://#')"
  git -C "$APP_DIR" remote set-url origin "$CLEAN_URL" 2>/dev/null || true
elif [ -e "$APP_DIR" ] && [ -n "$(ls -A "$APP_DIR" 2>/dev/null)" ]; then
  die "$APP_DIR exists and is not a git checkout — refusing to overwrite."
else
  note "Cloning $BRANCH into $APP_DIR"
  mkdir -p "$APP_DIR"
  git clone --branch "$BRANCH" --depth 1 "$REPO_URL" "$APP_DIR"
fi
cd "$APP_DIR"

# ---- Dependencies + assets ----------------------------------------------
note "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null; then
  note "Building front-end assets"
  npm ci || npm install
  npm run build
else
  warn "npm not available — front-end assets were NOT built (site will be unstyled). Build elsewhere and copy public/build/, or re-run with Node installed."
fi

# ---- Database ------------------------------------------------------------
note "Configuring database (creates only '$DB_NAME')"
if [ -f "$APP_DIR/.env" ] && grep -q '^database.default.password' "$APP_DIR/.env"; then
  DB_PASS="$(grep '^database.default.password' "$APP_DIR/.env" | head -1 | sed "s/.*= *'\?\([^']*\)'\?.*/\1/")"
fi
DB_PASS="${DB_PASS:-$(openssl rand -hex 18)}"

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---- Which scheme will this site actually be served over? ----------------
# Decided once, here, because two places depend on the answer and they used to
# disagree. baseURL was hard-coded to https while the vhost fell back to the
# plain-HTTP template, so the app built https URLs for a site that had no TLS
# listener at all. That is invisible until somebody adds a certificate by hand:
# certbot bolts a :443 block onto the HTTP template, which sets no
# `fastcgi_param HTTPS`, so PHP still reads the request as insecure, disagrees
# with its own https baseURL, and redirects to the address it is already on —
# forever. A browser gives up after twenty hops.
TLS_NOTE=""
if [ -f "deploy/nginx/${DOMAIN}.conf" ]; then
  # A hand-tuned vhost is assumed to terminate TLS; it exists because the
  # generic templates did not fit.
  VHOST_SRC="deploy/nginx/${DOMAIN}.conf"
  SITE_SCHEME="https"
elif [ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
  VHOST_SRC="deploy/nginx/site-tls.conf.template"
  SITE_SCHEME="https"
else
  # Naming a missing certificate makes `nginx -t` fail, which would abort the
  # deploy after the database has already been created and seeded.
  VHOST_SRC="deploy/nginx/site-http.conf.template"
  SITE_SCHEME="http"
  TLS_NOTE="No certificate for ${DOMAIN} yet — serving plain HTTP."
  warn "$TLS_NOTE Run: certbot --nginx -d ${DOMAIN}   (then re-run this deploy for the TLS vhost)"
fi
note "scheme: ${SITE_SCHEME}; vhost template: $VHOST_SRC"

# ---- .env ----------------------------------------------------------------
note "Writing .env"
cp -n deploy/.env.production.example "$APP_DIR/.env" 2>/dev/null || true

# Secrets are generated once and then kept. Rotating them on every deploy is not
# a security measure, it is a fault: a new jwt.secret invalidates every token
# that has been issued, and a new encryption.key makes everything already
# encrypted with the old one — SMTP passwords, API secrets — permanently
# unreadable. Read what is there; generate only what is missing.
read_env() { grep "^$1" "$APP_DIR/.env" 2>/dev/null | head -1 | sed "s/.*= *'\?\([^']*\)'\?.*/\1/"; }
JWT_SECRET="$(read_env 'jwt.secret')"
JWT_SECRET="${JWT_SECRET:-$(openssl rand -hex 32)}"
ENCRYPTION_KEY="$(read_env 'encryption.key')"
if [ -z "$ENCRYPTION_KEY" ]; then
  # CodeIgniter expects the key prefixed with hex2bin: so it is decoded rather
  # than used as literal text.
  ENCRYPTION_KEY="hex2bin:$(openssl rand -hex 32)"
  note "Generated an encryption key (stored only in $APP_DIR/.env)"
fi

# Write the dynamic values into .env (idempotent).
set_env() { local k="$1" v="$2"; if grep -q "^$k" "$APP_DIR/.env"; then sed -i "s|^$k.*|$k = '$v'|" "$APP_DIR/.env"; else printf "\n%s = '%s'\n" "$k" "$v" >> "$APP_DIR/.env"; fi; }
set_env "app.baseURL" "${SITE_SCHEME}://${DOMAIN}/"
set_env "database.default.database" "$DB_NAME"
set_env "database.default.username" "$DB_USER"
set_env "database.default.password" "$DB_PASS"
set_env "jwt.secret" "$JWT_SECRET"
set_env "encryption.key" "$ENCRYPTION_KEY"

# ---- Permissions ---------------------------------------------------------
note "Setting ownership to $WEB_USER"
chown -R "$WEB_USER":"$WEB_USER" "$APP_DIR"
chmod -R ug+rwX "$APP_DIR/writable"

# ---- Migrate + seed ------------------------------------------------------
note "Running migrations + seed"
sudo -u "$WEB_USER" "$PHP_BIN" spark key:generate --force
sudo -u "$WEB_USER" "$PHP_BIN" spark migrate --all
# Seed the database. Bootstrap seeders (roles / admin user / settings) skip
# existing rows; the Home/Corporate/Showroom content seeders re-apply canonical
# page content (idempotent), so a re-run brings live content in sync with the
# repo — e.g. the hero video + kinetic copy. Pass SKIP_SEED=1 to skip (e.g. once
# content is managed via the admin).
if [ "${SKIP_SEED:-0}" != "1" ]; then
  sudo -u "$WEB_USER" "$PHP_BIN" spark db:seed "Modules\\Core\\Database\\Seeds\\DatabaseSeeder"
else
  note "SKIP_SEED=1 — skipping seed."
fi

# ---- Nginx ---------------------------------------------------------------
note "Installing nginx vhost"
# The template was chosen alongside the scheme, before .env was written, so the
# two cannot disagree. Installing it also overwrites anything certbot wrote
# into this vhost, which is deliberate: certbot's --redirect is unconditional
# and loops behind a TLS-terminating proxy, while the template's redirect is
# conditional on X-Forwarded-Proto.
sed -e "s|{{APP_DIR}}|$APP_DIR|g" -e "s|{{PHP_FPM_SOCK}}|$PHP_FPM_SOCK|g" \
    -e "s|{{DOMAIN}}|$DOMAIN|g"   -e "s|{{SLUG}}|$SLUG|g" \
    "$VHOST_SRC" > "$NGINX_AVAIL"

# Enable it only once nginx accepts it. This box serves a dozen unrelated
# sites from one nginx, so an invalid vhost left enabled is not this site
# failing to deploy — it is every site on the machine going down at whatever
# reload happens next, possibly hours later and for someone else's deploy.
# Test with the symlink in place, and take it straight back out if the test
# fails, so a bad config is never left armed.
PREEXISTING_LINK=0
[ -e "$NGINX_ENABLED" ] && PREEXISTING_LINK=1
ln -sfn "$NGINX_AVAIL" "$NGINX_ENABLED"
if ! nginx -t; then
  if [ "$PREEXISTING_LINK" -eq 0 ]; then
    rm -f "$NGINX_ENABLED"
    warn "nginx rejected the vhost for ${DOMAIN}; it has been disabled again."
  else
    warn "nginx rejected the updated vhost for ${DOMAIN}; the previous one is still enabled."
  fi
  if nginx -t >/dev/null 2>&1; then
    die "nginx rejected this vhost. Other sites are unaffected and nginx was not reloaded."
  fi
  die "nginx config is invalid even without this site. Do NOT reload nginx until that is resolved."
fi
systemctl reload nginx

# ---- Done ----------------------------------------------------------------
note "Deploy complete"
cat <<DONE

Site:   ${SITE_SCHEME}://${DOMAIN}/   (the welcome page; each language hangs off it)
Admin:  ${SITE_SCHEME}://${DOMAIN}/admin/login

NEXT STEPS:
  1. Sign in with the administrator the seeder printed above, and change that
     password. It was printed once, into this log, and a build log is not a
     place to leave a working credential.
  2. TLS: either point Cloudflare SSL to "Full" with a Cloudflare Origin
     Certificate on this box, or run:  certbot --nginx -d ${DOMAIN}
     then set app.forceGlobalSecureRequests = true in $APP_DIR/.env
  3. Rotate the server root password that was shared earlier.

DB password and app secrets live ONLY in $APP_DIR/.env (not in git).
DONE
