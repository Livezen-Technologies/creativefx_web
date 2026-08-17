# Deploying Norlanka — plain Nginx + PHP-FPM

A git-pull deployment that adds **only** the `norlankamfg.livezencloud.com`
site. It does **not** touch other vhosts, databases, or global PHP config.

## What it creates (and nothing else)
- App code: `/var/www/norlankamfg` (this branch)
- Database: `norlanka_prod` + user `norlanka_prod@localhost` (created if missing)
- Nginx vhost: `/etc/nginx/sites-available/norlankamfg.livezencloud.com.conf` (refuses to overwrite a foreign file)
- App secrets (`.env`, DB password, JWT secret, encryption key): generated on the server, **never** in git

## Prerequisites on the server
- Ubuntu/Debian with **Nginx**, **PHP-FPM 8.3+** (`intl mbstring mysqli gd curl json` extensions), **MySQL 8** running, `git`, `curl`, `openssl`.
- Composer and Node are installed automatically if missing (Node is only used to build assets; pass `SKIP_NODE=1` to skip and build elsewhere).
- DNS / Cloudflare already points the domain at this server (it does).
- Git access to this repo from the server: a **deploy key** or a **personal access token** in `REPO_URL` (the repo is private).

## First deploy
```bash
# as root on the server
REPO_URL='https://<GITHUB_TOKEN>@github.com/livezen-technologies/norlanka_web.git' \
bash <(curl -fsSL https://raw.githubusercontent.com/livezen-technologies/norlanka_web/claude/awesome-planck-01cc95/deploy/setup.sh)

# …or clone first and run locally:
git clone --branch claude/awesome-planck-01cc95 \
  'https://<GITHUB_TOKEN>@github.com/livezen-technologies/norlanka_web.git' /tmp/norlanka
REPO_URL='https://<GITHUB_TOKEN>@github.com/livezen-technologies/norlanka_web.git' \
  bash /tmp/norlanka/deploy/setup.sh
```
The script prints a summary and asks for confirmation before changing anything
(set `ASSUME_YES=1` to skip the prompt). Override any default inline, e.g.
`APP_DIR=/srv/norlanka DB_NAME=norlanka_live bash deploy/setup.sh`.

After it finishes:
- Site: `http://norlankamfg.livezencloud.com/` → redirects to `/en`
- Admin: `/admin/login` — **admin@norlanka.local / norlanka123** (change immediately)

## Deploy via GitHub Actions (no SSH from your machine)

If you'd rather not SSH in yourself, the workflow at
`.github/workflows/deploy.yml` runs `setup.sh` on the server from a GitHub
runner. One-time setup:

1. Repo → **Settings → Secrets and variables → Actions → New repository secret**:
   - `DEPLOY_HOST` = `178.105.165.144`
   - `DEPLOY_USER` = `root`
   - `DEPLOY_PASSWORD` = the server password  *(or `DEPLOY_SSH_KEY` = a private key — preferred)*
2. Repo → **Actions → "Deploy (norlankamfg.livezencloud.com)" → Run workflow**.

The runner copies `setup.sh` to the server and runs it (cloning this repo with the
run's `GITHUB_TOKEN`). It's manual-trigger only and never deploys on push.

## TLS
The vhost listens on :80. Choose one:
- **Cloudflare**: install a Cloudflare *Origin Certificate* on the box and set the
  domain's SSL mode to **Full (strict)**; or
- **certbot**: `certbot --nginx -d norlankamfg.livezencloud.com`

Then set `app.forceGlobalSecureRequests = true` in `/var/www/norlankamfg/.env`.
The vhost already forwards Cloudflare's `X-Forwarded-Proto` so the app builds
`https://` URLs.

## Updating later
```bash
bash /var/www/norlankamfg/deploy/update.sh
```
Pulls the branch, reinstalls deps, rebuilds assets, runs migrations, reloads php-fpm.

## Taking the site offline

```bash
cd /var/www/norlankamfg
sudo -u www-data php spark maintenance on --until "18 August, 09:00"
sudo -u www-data php spark maintenance status   # confirms, and prints the preview link
sudo -u www-data php spark maintenance off      # back online
```

Visitors then get the offline page with a 503; `/admin` stays reachable, so the
same switch is in **Admin → Maintenance**. If the database is down, the flag
file still works on its own:

```bash
sudo -u www-data touch /var/www/norlankamfg/writable/maintenance.flag   # offline
sudo rm /var/www/norlankamfg/writable/maintenance.flag                  # online
```

The flag is untracked, so `update.sh` (`git reset --hard`) leaves it alone —
a deploy run during an outage keeps the site dark until you switch it back.

## Rollback
```bash
cd /var/www/norlankamfg && git reset --hard <previous_commit> \
  && composer install --no-dev -o && npm run build \
  && sudo -u www-data php spark migrate --all
```

## Safety notes
- Review `setup.sh` before running — it is intentionally conservative and aborts
  rather than overwrite anything it didn't create.
- Secrets are generated server-side; rotate the shared root password.
- The DB user is granted privileges on `norlanka_prod` **only**.
