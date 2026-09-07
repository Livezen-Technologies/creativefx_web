#!/usr/bin/env bash
#
# First-time deploy for plain Nginx + PHP-FPM.
#
# One script, several sites: the DOMAIN / APP_DIR / DB_NAME defaults below are
# the first site this was written for, and every other site passes its own.
#
# ISOLATION GUARANTEES — this script only ADDS a new site:
#   • app code        -> $APP_DIR (its own directory)
#   • database        -> $DB_NAME / user $DB_USER (created if absent; nothing else touched)
#   • nginx vhost     -> /etc/nginx/sites-available/$DOMAIN.conf (refuses to clobber)
# It never edits other vhosts, never drops/alters other databases, and never
# changes global PHP config. Review it before running.
#
# Usage (as root):
#   REPO_URL='https://<token>@github.com/<owner>/<repo>.git' \
#   DOMAIN=example.livezencloud.com APP_DIR=/var/www/example DB_NAME=example_prod \
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

# The template ships placeholders, not blanks — jwt.secret and the database
# password are both the literal string __GENERATED_ON_SERVER__ so that a
# half-configured .env is obvious rather than silently empty. That makes
# `read_env` return a non-empty value on a fresh install, and a plain
# `${VAR:-$(openssl rand …)}` therefore keeps the placeholder: the site would
# deploy with a JWT signing secret that is committed to the repository and
# identical on every install, which is to say no secret at all. Anything
# matching the placeholder counts as absent.
unset_or_placeholder() { [ -z "$1" ] || [ "$1" = "__GENERATED_ON_SERVER__" ]; }

JWT_SECRET="$(read_env 'jwt.secret')"
if unset_or_placeholder "$JWT_SECRET"; then
  JWT_SECRET="$(openssl rand -hex 32)"
  note "Generated a JWT secret (stored only in $APP_DIR/.env)"
fi

ENCRYPTION_KEY="$(read_env 'encryption.key')"
if unset_or_placeholder "$ENCRYPTION_KEY"; then
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
# No `spark key:generate` here, deliberately. It writes a *new* encryption.key
# with --force, which would undo the preservation above nine lines after it was
# done: every deploy would rotate the key, making anything already encrypted
# with the old one — the SMTP password, API secrets — permanently unreadable,
# and changing every ip_hash the feedback, booking and discussion tables have
# already stored. The key is written into .env by set_env above; missing means
# generated once, present means kept.
sudo -u "$WEB_USER" "$PHP_BIN" spark migrate --all
# Seed the database. Bootstrap seeders (roles / admin user / settings) skip
# existing rows; the content seeders will not overwrite editorial work — pages
# carry an is_custom flag an admin edit sets, list tables seed only while empty,
# and the rest upsert by slug and stop at a row somebody has touched. So a
# re-run brings a fresh install up to the repo without undoing the CMT's edits.
# Pass SKIP_SEED=1 to skip entirely.
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

# ---- Scheduled work ------------------------------------------------------
note "Installing the seat sweeper"
# A seat hold lasts fifteen minutes and something has to be the thing that
# notices. Nothing was: `seats:manage release` existed and no deploy ever ran
# it, so on a live site every abandoned checkout would have left its seats
# reserved for ever and the class would have sold out to nobody. The symptom is
# slow and looks like demand.
#
# One file in /etc/cron.d per site, named for the slug, so this only ever adds
# its own — the same rule the vhost and the database follow. Rewritten on every
# deploy rather than appended to, so a redeploy cannot leave two.
#
# `reconcile` runs after `release` because releasing a hold is what makes the
# cached counter wrong; hourly is enough for a counter that is only ever a
# cache of a count the booking path takes under a lock.
# An absolute path: cron's PATH is not a login shell's, and "php" resolving on
# the deploying operator's terminal says nothing about whether it resolves at
# 03:05 under www-data.
PHP_ABS="$(command -v "$PHP_BIN" 2>/dev/null || printf '%s' "$PHP_BIN")"
case "$PHP_ABS" in
  /*) : ;;
  *)  warn "Could not resolve '$PHP_BIN' to an absolute path; the seat sweeper may not run." ;;
esac

CRON_FILE="/etc/cron.d/${SLUG}-seats"
if [ -d /etc/cron.d ]; then
  cat > "$CRON_FILE" <<CRON
# MyLearnPlus seat maintenance for ${DOMAIN}. Written by deploy/setup.sh.
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Expired holds go back on sale every five minutes.
*/5 * * * * ${WEB_USER} cd ${APP_DIR} && ${PHP_ABS} spark seats:manage release >/dev/null 2>&1

# The denormalised seat counters are re-derived hourly, at a minute nothing
# else is using.
17 * * * * ${WEB_USER} cd ${APP_DIR} && ${PHP_ABS} spark seats:manage reconcile >/dev/null 2>&1
CRON
  chmod 0644 "$CRON_FILE"
  note "seat sweeper: $CRON_FILE (release every 5 min, reconcile hourly)"
else
  warn "No /etc/cron.d on this host — seat holds will NOT be released automatically."
  warn "Run '${PHP_ABS} spark seats:manage release' from a scheduler, or expired holds keep their seats."
fi

# ---- Done ----------------------------------------------------------------
note "Deploy complete"
cat <<DONE

Site:   ${SITE_SCHEME}://${DOMAIN}/
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
