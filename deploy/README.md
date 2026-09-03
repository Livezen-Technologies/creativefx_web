# Deploying — plain Nginx + PHP-FPM

A git-pull deployment that **adds** one site to a box and touches nothing else:
not another vhost, not another database, not the global PHP config.

This branch deploys `tshda.livezencloud.com`. The same script serves the other
sites on the box; each passes its own `DOMAIN`, `APP_DIR` and `DB_NAME`, and the
defaults in `setup.sh` belong to the first site it was written for.

## What it creates (and nothing else)
- App code: `$APP_DIR` — `/var/www/tshda` for this site
- Database: `$DB_NAME` + user `$DB_NAME@localhost`, created if missing
- Nginx vhost: `/etc/nginx/sites-available/$DOMAIN.conf` — refuses to overwrite
  a file it did not write
- App secrets (`.env`, DB password, JWT secret, encryption key): generated on
  the server, **never** in git, and preserved across deploys

## Prerequisites on the server
- Ubuntu/Debian with **Nginx**, **PHP-FPM 8.3+** (`intl mbstring mysqli gd curl
  json`), **MySQL 8** running, plus `git`, `curl`, `openssl`.
- Composer and Node are installed automatically if missing. Node is only used to
  build assets — pass `SKIP_NODE=1` to skip it and build elsewhere. Nothing on
  the running site needs a Node runtime.
- DNS already points the domain at this server.
- Git access to this repo from the server: a deploy key, or a personal access
  token in `REPO_URL`.

## Deploy via GitHub Actions (the usual route)

`.github/workflows/deploy.yml` runs `setup.sh` on the server from a GitHub
runner, so nobody needs to SSH in. Manual trigger only — it never deploys on
push.

Actions → **Deploy** → Run workflow. Dispatched from the `tshda` branch, the
four inputs already default to this site, so a plain re-deploy needs no edits:

| Input | Default on this branch |
|---|---|
| `branch` | `tshda` |
| `domain` | `tshda.livezencloud.com` |
| `app_dir` | `/var/www/tshda` |
| `db_name` | `tshda_prod` |

Deploying a *different* site means changing all four together. They are not
independent: `domain` with somebody else's `db_name` runs this branch's
migrations against that site's live database.

Credentials resolve as repo secret → dispatch input: set `DEPLOY_HOST`,
`DEPLOY_USER` and `DEPLOY_SSH_KEY` (preferred) or `DEPLOY_PASSWORD` under
Settings → Secrets and variables → Actions. A password passed as an input is
recorded in the run — rotate it afterwards.

## By hand

```bash
# as root on the server
REPO_URL='https://<GITHUB_TOKEN>@github.com/Livezen-Technologies/creativefx_web.git' \
BRANCH=tshda DOMAIN=tshda.livezencloud.com \
APP_DIR=/var/www/tshda DB_NAME=tshda_prod DB_USER=tshda_prod \
bash deploy/setup.sh
```

The script prints what it is about to do and asks for confirmation; set
`ASSUME_YES=1` to skip the prompt. `SKIP_SEED=1` skips the seeders.

The seeder prints the first administrator's password once, on creation. Sign in
and change it — a build log is not a place to leave a working credential.

## TLS

The first deploy serves plain HTTP, deliberately: naming a certificate that does
not exist yet makes `nginx -t` fail, and it would fail *after* the database has
been created and seeded. Then:

1. Actions → **Server ops** → `setup-tls-certbot`, with the same `domain`.
2. Re-run **Deploy**. It installs the TLS vhost and sets `app.baseURL` to
   `https://`.

The vhost forwards Cloudflare's `X-Forwarded-Proto`, so the app agrees with the
edge about the scheme whether the zone is on Flexible or Full — a disagreement
there is what produces an endless redirect.

## Updating later

Re-run the Deploy workflow. It pulls the branch, reinstalls dependencies,
rebuilds assets, runs migrations and re-seeds. The seeders will not overwrite
content the CMT has edited: pages carry an `is_custom` flag, list tables seed
only while empty, and the rest upsert by slug and stop at a row somebody has
touched.

## Rollback

```bash
cd /var/www/tshda && git reset --hard <previous_commit> \
  && composer install --no-dev -o && npm run build \
  && sudo -u www-data php spark migrate --all
```

## Safety notes

- Read `setup.sh` before running it. It is deliberately conservative and aborts
  rather than overwrite anything it did not create.
- Secrets are generated server-side and kept. Rotating `jwt.secret` invalidates
  every issued token; rotating `encryption.key` makes everything already
  encrypted — SMTP passwords, API secrets — permanently unreadable.
- The database user is granted privileges on its own database only.
- Rotate any server password that has been shared or passed as a workflow input.
