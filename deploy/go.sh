#!/usr/bin/env bash
#
# CreativeFX — one-command deploy.
#
#   bash deploy/go.sh
#   DOMAIN=magiccorn.livezencloud.com bash deploy/go.sh
#
# Works out whether this is a first install or an update, backs the database
# up before it changes anything, then runs the right script. Everything it
# does is printed before it does it.
#
# Run as root on the web server.
set -euo pipefail

DOMAIN="${DOMAIN:-magiccorn.livezencloud.com}"
BRANCH="${BRANCH:-claude/maintenance-mode-build-7b22jh}"
REPO_URL="${REPO_URL:-https://github.com/Livezen-Technologies/creativefx_web.git}"
BACKUP_DIR="${BACKUP_DIR:-/root/creativefx-backups}"
ASSUME_YES="${ASSUME_YES:-0}"

bold() { printf '\033[1m%s\033[0m\n' "$*"; }
warn() { printf '\033[33m%s\033[0m\n' "$*"; }
die()  { printf '\033[31m%s\033[0m\n' "$*" >&2; exit 1; }

[ "$(id -u)" -eq 0 ] || die "Run as root (sudo bash deploy/go.sh)."

# --- 1. Locate an existing install ----------------------------------------
# Prefer an explicit APP_DIR; otherwise read the web root out of the vhost
# that serves this domain. The app dir is the parent of .../public.
APP_DIR="${APP_DIR:-}"
if [ -z "$APP_DIR" ]; then
  VHOST="$(grep -rl "server_name[^;]*\b${DOMAIN}\b" /etc/nginx/sites-enabled/ 2>/dev/null | head -1 || true)"
  if [ -n "$VHOST" ]; then
    ROOT="$(grep -m1 -E '^\s*root\s+' "$VHOST" | awk '{print $2}' | tr -d ';' || true)"
    [ -n "$ROOT" ] && APP_DIR="$(dirname "$ROOT")"
  fi
fi

MODE="install"
if [ -n "$APP_DIR" ] && [ -d "$APP_DIR/.git" ]; then
  MODE="update"
fi

bold "== CreativeFX deploy =="
echo "  domain : $DOMAIN"
echo "  branch : $BRANCH"
echo "  app dir: ${APP_DIR:-<none found — will provision a new site>}"
echo "  mode   : $MODE"

# --- 2. Sanity-check an existing checkout ---------------------------------
if [ "$MODE" = "update" ]; then
  ORIGIN="$(git -C "$APP_DIR" remote get-url origin 2>/dev/null || echo '?')"
  echo "  origin : $ORIGIN"
  case "$ORIGIN" in
    *creativefx_web*|*norlanka_web*) : ;;
    *) die "The checkout at $APP_DIR points at '$ORIGIN', which is not this project.
Refusing to reset a repository that is not ours. Set APP_DIR explicitly if this is wrong." ;;
  esac
fi

if [ "$ASSUME_YES" != "1" ]; then
  read -r -p "Proceed? [y/N] " ans
  case "$ans" in y|Y|yes|YES) : ;; *) echo "Aborted."; exit 0 ;; esac
fi

# --- 3. Back up the database before anything changes ----------------------
if [ "$MODE" = "update" ] && [ -f "$APP_DIR/.env" ]; then
  DB="$(grep -m1 -E '^\s*database\.default\.database' "$APP_DIR/.env" | cut -d= -f2 | tr -d " '\"" || true)"
  if [ -n "$DB" ] && command -v mysqldump >/dev/null; then
    mkdir -p "$BACKUP_DIR"
    OUT="$BACKUP_DIR/${DB}-$(date +%F-%H%M).sql"
    bold "==> backing up $DB"
    if mysqldump "$DB" > "$OUT" 2>/dev/null; then
      echo "  saved $OUT ($(du -h "$OUT" | cut -f1))"
    else
      rm -f "$OUT"
      warn "  mysqldump failed — continuing without a backup."
    fi
  fi
fi

# --- 4. Deploy ------------------------------------------------------------
if [ "$MODE" = "update" ]; then
  bold "==> updating $APP_DIR"
  APP_DIR="$APP_DIR" BRANCH="$BRANCH" bash "$APP_DIR/deploy/update.sh"
else
  bold "==> provisioning a new site"
  DOMAIN="$DOMAIN" BRANCH="$BRANCH" REPO_URL="$REPO_URL" ASSUME_YES=1 \
    bash <(curl -fsSL "https://raw.githubusercontent.com/Livezen-Technologies/creativefx_web/${BRANCH}/deploy/setup.sh")
  APP_DIR="${APP_DIR:-/var/www/magiccorn}"
fi

# --- 5. Report ------------------------------------------------------------
echo
bold "== done =="
if [ -d "$APP_DIR" ]; then
  ( cd "$APP_DIR" && sudo -u www-data php spark maintenance status 2>/dev/null || true )
fi
cat <<EOF

The site ships in maintenance mode on purpose, so you can check it before
visitors do. When you are happy with it:

  cd $APP_DIR && sudo -u www-data php spark maintenance off

Admin: https://$DOMAIN/admin/login   (change the seeded password immediately)
EOF
