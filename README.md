# Norlanka Corporate Website & Virtual Showroom

A multilingual corporate website and virtual showroom for Norlanka (apparel
manufacturer), built on a **modular CodeIgniter 4 CMS** with a **Vite + Tailwind
+ Alpine.js + GSAP + Three.js** front-end.

This repository contains **Milestone 1 — the project foundation**: the runnable
modular architecture, the core database schema, and the flagship **Home launch
experience** (Netflix-style multi-language audio switching with subtitles and
GSAP scroll storytelling). The remaining modules (virtual showroom, full admin,
careers, CRM, ESG dashboards) are scaffolded as skeletons to build on.

---

## Tech stack

| Layer    | Tech |
|----------|------|
| Backend  | CodeIgniter 4.7, PHP 8.3+, MySQL 8 (SQLite for local dev), Redis, JWT auth |
| Frontend | TailwindCSS, Alpine.js, GSAP + ScrollTrigger, Swiper.js, Three.js, Vite |
| Infra    | Docker, Nginx (+ AWS S3 / Cloudflare / Meilisearch / GA4 integration points) |

Brand: primary `#CF2030`, secondary `#000000`; fonts **K2D** (primary) +
**Avenir** (secondary, falls back to Montserrat — Avenir is licensed and not
bundled). Locales: English, Japanese, Spanish, Chinese.

---

## Modular architecture

All domain code lives in independent, namespaced **code modules** under
`modules/`. Each owns its routes, controllers, models, migrations, views and
language files, auto-discovered by CI4 — `app/` holds only the global shell.

```
modules/
  Core/        shared layout, partials, LocaleFilter, i18n helper, settings
  Site/        public front-end: Home launch experience + generic CMS renderer
  Auth/        users/roles/permissions, JWT library + filter, /api/auth
  Cms/         pages / page_sections / page_blocks + Home content seeder
  Translation/ translations table + manager (skeleton)
  Media/       media_library (local/S3 abstraction)
  Catalog/     product_categories / products (13 categories seeded)
  Showroom/    showroom_categories / showroom_products (skeleton)
  Video/       videos / video_tracks / video_subtitles (powers the Home film)
  Esg/ Careers/ Crm/ Analytics/   domain tables + models (skeleton)
  Admin/       JWT-gated dashboard stub + login
```

Module namespaces are registered in `app/Config/Autoload.php`; migration files
use globally-ordered timestamp prefixes so cross-module foreign keys resolve.

---

## Quick start (no Docker — uses SQLite)

```bash
composer install
cp .env.example .env          # already configured for SQLite by default
php spark key:generate
php spark migrate --all        # NOTE: --all runs every module's migrations
php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"

npm install
npm run build                  # builds assets into public/build
php spark serve --port 8080
# open http://localhost:8080/  → redirects to /en
```

Front-end dev with hot reload: `npm run dev` (Vite on :5173) alongside
`php spark serve`.

## Quick start (Docker — uses MySQL 8 + Redis)

```bash
cp .env.example .env           # switch DB to the MySQL (Option B) block
docker compose up -d --build
docker compose exec app php spark migrate --all
docker compose exec app php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"
npm install && npm run build
# open http://localhost:8080/
```

---

## The Home launch experience

The hero is **CMS-driven** from the seeded `videos` / `video_tracks` /
`video_subtitles` rows. Technically:

- A single animated brand backdrop is the visual (an actual background film can
  be dropped in later by setting `videos.src_path` — no code change).
- Each language has its own `<audio>` track; the **active audio element is the
  master clock**, and only one plays at a time (Netflix-style switching).
- **Subtitles** are WebVTT files rendered manually from the active track's
  `cuechange` events, so they work with or without a `<video>` surface.
- **GSAP ScrollTrigger** drives the storytelling sections (reveals, animated
  stat counters), guarded by `prefers-reduced-motion`.

Placeholder media (self-contained, generated — no licensing) lives in
`public/media/audio/*.wav` and `public/media/subtitles/*.vtt`. Swap in real
S3/CDN assets by updating the `videos`/`video_tracks` rows (or via the future
media manager).

---

## Admin & API

- `POST /api/auth/login` → returns a JWT. Dev credentials:
  **`admin@norlanka.local` / `norlanka123`** (change in production).
- `GET /api/auth/me` and `GET /admin` require `Authorization: Bearer <token>`.
- `GET /admin/login` is a public page that performs the login flow.

User roles seeded: Super Admin, Content Manager, ESG Manager, HR Manager,
Marketing Manager, Viewer.

---

## Notes / conventions

- **`php spark migrate --all`** — module migrations only run with `--all`.
- Translatable content is stored as JSON locale-maps and resolved by the
  `t_field()` helper (falls back to `en`).
- Large video binaries are **not** committed — only small generated placeholders.
- The locale filter alias is `applocale` (not `locale`, which collides with
  PHP's case-insensitive built-in `\Locale` class).
- Localized routes use a constrained `(:locale)` placeholder so `/admin` and
  `/api` are never shadowed.
