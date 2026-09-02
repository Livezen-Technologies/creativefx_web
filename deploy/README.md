# Deploying CreativeFX — plain Nginx + PHP-FPM

A git-pull deployment that adds **only** the `magiccorn.livezencloud.com`
site. It does **not** touch other vhosts, databases, or global PHP config.

## What it creates (and nothing else)
- App code: `/var/www/magiccorn` (this branch)
- Database: `magiccorn_prod` + user `magiccorn_prod@localhost` (created if missing)
- Nginx vhost: `/etc/nginx/sites-available/magiccorn.livezencloud.com.conf` (refuses to overwrite a foreign file)
- App secrets (`.env`, DB password, JWT secret, encryption key): generated on the server, **never** in git

## Prerequisites on the server
- Ubuntu/Debian with **Nginx**, **PHP-FPM 8.3+** (`intl mbstring mysqli gd curl json` extensions), **MySQL 8** running, `git`, `curl`, `openssl`.
- Composer and Node are installed automatically if missing (Node is only used to build assets; pass `SKIP_NODE=1` to skip and build elsewhere).
- DNS / Cloudflare already points the domain at this server (it does).
- Git access to this repo from the server: a **deploy key** or a **personal access token** in `REPO_URL` (the repo is public, so no token is needed).

## One command (recommended)

```bash
bash <(curl -fsSL https://raw.githubusercontent.com/Livezen-Technologies/creativefx_web/claude/maintenance-mode-build-7b22jh/deploy/go.sh)
```

`go.sh` works out whether the domain already has an install (reading the web
root out of its nginx vhost), backs the database up before it changes
anything, then runs `update.sh` or `setup.sh` accordingly. It prints the plan
and waits for confirmation. It refuses to touch a checkout whose `origin` is
not this project.

Override anything inline: `DOMAIN=…`, `APP_DIR=…`, `BRANCH=…`, `ASSUME_YES=1`.

## First deploy
```bash
# as root on the server
REPO_URL='https://github.com/Livezen-Technologies/creativefx_web.git' \
bash <(curl -fsSL https://raw.githubusercontent.com/Livezen-Technologies/creativefx_web/claude/maintenance-mode-build-7b22jh/deploy/setup.sh)

# …or clone first and run locally:
git clone --branch claude/maintenance-mode-build-7b22jh \
  'https://github.com/Livezen-Technologies/creativefx_web.git' /tmp/creativefx
REPO_URL='https://github.com/Livezen-Technologies/creativefx_web.git' \
  bash /tmp/creativefx/deploy/setup.sh
```
The script prints a summary and asks for confirmation before changing anything
(set `ASSUME_YES=1` to skip the prompt). Override any default inline, e.g.
`APP_DIR=/srv/creativefx DB_NAME=creativefx_live bash deploy/setup.sh`.

After it finishes:
- Site: `http://magiccorn.livezencloud.com/` → redirects to `/en`
- Admin: `/admin/login` — **admin@norlanka.local / norlanka123** (change immediately)

## Deploy via GitHub Actions (no SSH from your machine)

If you'd rather not SSH in yourself, the workflow at
`.github/workflows/deploy.yml` runs `setup.sh` on the server from a GitHub
runner. One-time setup:

1. Repo → **Settings → Secrets and variables → Actions → New repository secret**:
   - `DEPLOY_HOST` = the server address
   - `DEPLOY_USER` = `root`
   - `DEPLOY_PASSWORD` = the server password  *(or `DEPLOY_SSH_KEY` = a private key — preferred)*
2. Repo → **Actions → "Deploy (magiccorn.livezencloud.com)" → Run workflow**.

The runner copies `setup.sh` to the server and runs it (cloning this repo with the
run's `GITHUB_TOKEN`). It's manual-trigger only and never deploys on push.

## TLS
The vhost listens on :80. Choose one:
- **Cloudflare**: install a Cloudflare *Origin Certificate* on the box and set the
  domain's SSL mode to **Full (strict)**; or
- **certbot**: `certbot --nginx -d magiccorn.livezencloud.com`

Then set `app.forceGlobalSecureRequests = true` in `/var/www/magiccorn/.env`.
The vhost already forwards Cloudflare's `X-Forwarded-Proto` so the app builds
`https://` URLs.

## Updating later
```bash
bash /var/www/magiccorn/deploy/update.sh
```
Pulls the branch, reinstalls deps, rebuilds assets, runs migrations, reloads php-fpm.

## Taking the site offline

```bash
cd /var/www/magiccorn
sudo -u www-data php spark maintenance on --until "18 August, 09:00"
sudo -u www-data php spark maintenance status   # confirms, and prints the preview link
sudo -u www-data php spark maintenance off      # back online
```

Visitors then get the offline page with a 503; `/admin` stays reachable, so the
same switch is in **Admin → Maintenance**. If the database is down, the flag
file still works on its own:

```bash
sudo -u www-data touch /var/www/magiccorn/writable/maintenance.flag   # offline
sudo rm /var/www/magiccorn/writable/maintenance.flag                  # online
```

The flag is untracked, so `update.sh` (`git reset --hard`) leaves it alone —
a deploy run during an outage keeps the site dark until you switch it back.

## Rollback
```bash
cd /var/www/magiccorn && git reset --hard <previous_commit> \
  && composer install --no-dev -o && npm run build \
  && sudo -u www-data php spark migrate --all
```

## Safety notes
- Review `setup.sh` before running — it is intentionally conservative and aborts
  rather than overwrite anything it didn't create.
- Secrets are generated server-side; rotate the shared root password.
- The DB user is granted privileges on `magiccorn_prod` **only**.
