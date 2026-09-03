# Tea Small Holdings Development Authority — public portal

The trilingual public website of the Tea Small Holdings Development Authority
(TSHDA), Sri Lanka: English, Sinhala and Tamil, on a modular **CodeIgniter 4**
CMS with a **Vite + Tailwind + Alpine.js** front end.

Built against the technical proposal for TSHDA/2026/Website-15, whose clause
numbers appear throughout the code — a comment explaining *why* something is
the way it is is usually pointing at the clause that required it.

---

## What is here

| Clause | What it asks for | Where it lives |
|---|---|---|
| 3.9 A | Welcome page — trilingual entry, ICTA standards | `Modules\Tshda\Controllers\Welcome`, `Views/welcome.php` |
| 3.9 B | Modular home page, seven independently-managed modules | `Modules\Site\Controllers\Home`, `Views/home/index.php` |
| 3.9 C | About Us — history, vision, plans, staff, divisions, org chart | CMS pages (`PageSeeder`) + the `org_chart` block |
| 3.9 D/E | Service catalogue and the four subject areas | `services` table, `Controllers\Services` |
| 3.9 E.b | Society register, searchable | `societies` table, the `society_search` block |
| 3.9 E.d | Staff & contact directory, filterable | `offices` + `staff`, `Controllers\Directory` |
| 3.9 F | Statistics with charts and downloadable datasets | `statistics_datasets`, `Controllers\Statistics` |
| 3.9 G | Vacancies with closing dates and automatic expiry | `jobs` (Careers module), `Controllers\Vacancies` |
| 3.9 H | Document repository, eight categories, download counts | `documents`, `Controllers\Downloads` |
| 3.9 I | Photo and video galleries, news and event pages | `Controllers\Gallery`, News module |
| 3.9 J | Contact, offices map, tracked feedback and petitions | `Controllers\Contact`, `Controllers\Feedback` |
| 3.9 K | Categorised, searchable FAQs | `faqs`, `Controllers\Faqs` |
| 3.9 L | Human sitemap and XML sitemap | `Controllers\SitemapPage`, `Modules\Site\Controllers\Sitemap` |
| 3.1.II | Field Officer Portal | `Controllers\FieldOfficer`, `officer_submissions` |
| 3.1.IV | Hantana NTC booking, with capacity control | `programmes` + `programme_bookings`, `Controllers\Hantana` |
| 3.11 | Role-based editorial privileges | `Modules\Auth\Database\Seeds\RoleSeeder` |
| 3.12 | Search across everything, inside PDFs, script-normalised | `Libraries\SiteSearch`, `Libraries\DocumentText` |
| 3.13 | Alert subscriptions, double opt-in, one-click unsubscribe | `subscribers`, `Controllers\Subscribe` |
| 3.14 | Moderated discussion, closed-loop feedback tracking | `discussion_*`, `feedback` |
| 3.15 | Trilingual with in-place switching, hreflang, WCAG 2.1 AA | `alpine/langSwitcher.js`, `layouts/main.php` |
| 3.10 | Last-updated stamp, government links in every footer | `layouts/main.php`, `partials/footer.php` |
| 3.17 | Self-hosted traffic statistics | Analytics module, `/admin/analytics` |
| 3.12 | Help assistant — answers from the FAQ and pages | `Controllers\Assistant`, `partials/assistant.php` |

Controllers named without a namespace above are in `Modules\Tshda\Controllers`.

---

## Tech stack

| Layer | Tech |
|---|---|
| Backend | CodeIgniter 4.7, PHP 8.3+, MySQL 8 in production (SQLite for local dev) |
| Frontend | Tailwind CSS, Alpine.js, GSAP, Vite |
| Type | Noto Sans + Noto Sans Sinhala + Noto Sans Tamil, self-hosted |
| Infra | Nginx + PHP-FPM. No Node runtime on the production server — assets are built at deploy time and served as static files |

Palette: `#0F6B45` (the Authority green) and `#8A6A18` (gold), both measured
against WCAG 2.1 AA in the light and the dark grounds. Locales: `en`, `si`, `ta`.

---

## Modular architecture

Domain code lives in namespaced modules under `modules/`, each owning its
routes, controllers, models, migrations, views and language files, auto-
discovered by CI4. `app/` holds only the global shell.

```
modules/
  Core/        layout, partials, LocaleFilter, settings, mailer, scheduler
  Site/        public shell: home page, CMS block renderer, XML sitemap, 404
  Tshda/       the Authority's own subject matter — see below
  Cms/         pages / page_sections / page_blocks, menus, per-page SEO
  Auth/        users, roles, permissions, JWT
  Admin/       the console: CRUD screens, queues, settings, analytics
  Translation/ the translations table and its manager
  Media/       the media library
  News/        news, press releases, announcements, events
  Careers/     vacancies and applications
  Crm/         contact messages and leads
  Video/       video records with audio tracks and subtitles
  Analytics/   self-hosted page-view capture and reporting
```

`Modules\Tshda` carries what the platform did not already have: notices,
services, documents, FAQs, offices, staff, statistics, programmes and their
bookings, societies, discussion, subscribers, feedback and field-officer
submissions — with `SiteSearch`, `DocumentText` and `Reference` beside them.

Namespaces are registered in `app/Config/Autoload.php`; migrations use
globally-ordered timestamp prefixes so cross-module references resolve.

---

## Conventions

- **Translatable content is a JSON locale-map** (`{"en":…,"si":…,"ta":…}`)
  resolved at render time by `t_field()`, which falls back to English. A record
  can go live in English while Tamil is still in translation without the page
  breaking.
- **A person's name is not translatable; their designation is.** The same rule
  decides every column: if translating it would be nonsense, it is a plain
  column.
- **Seeders never overwrite editorial work.** Content pages check the
  `is_custom` flag an admin edit sets; list tables (menus, offices, notices,
  FAQs) seed only while empty; services and news upsert by slug and stop at a
  row somebody has touched. A release must not be able to undo the CMT's work.
- **The home page hero reads the Media library**, in filename order, first six.
  An image counts as a hero image if it is in the folder `hero` *or* carries the
  tag `hero` — the second because the first is a trap: uploading is the obvious
  action and the folder box is easy to miss, so the first real photograph landed
  in `uploads` and the hero went on showing its empty state. `php spark
  hero:images add <id|path>` promotes an image already uploaded, without moving
  the file. No table of its own: the `alt` is a locale map, so the picture is
  described in all three languages. No photographs is a supported state — the
  panel draws its own hillside, so the site ships before the photography is
  signed off.
- **The language is asked once, and the answer is shared.** `nl_locale` in
  localStorage is written by the header switcher and by the first-visit chooser,
  and read by the welcome page. Choosing anywhere means nothing asks again —
  and dismissing the chooser counts as choosing what is already on screen,
  because a dialog that reappears is nagging rather than asking.
- **Anything that moves the page must go through the scroll engine.** Lenis
  holds its own target position and animates towards it every frame, so a raw
  `window.scrollTo` is applied and then pulled back. `getLenis()` in
  `resources/js/smooth.js` returns the live instance.
- **The help assistant answers from this site, not from a model.** It reads the
  published FAQ first and the site search after it, so every answer is something
  the CMT wrote and can edit. Nothing third-party loads and no question leaves
  the server. When it has no answer it says so and offers the contact page — it
  never guesses, and it never claims a person is reading.
- **`php spark migrate --all`** — module migrations only run with `--all`.
- The locale filter alias is `applocale`, not `locale`, which collides with
  PHP's case-insensitive built-in `\Locale`.
- Localized routes use a constrained `(:locale)` placeholder, so `/admin` and
  `/api` are never shadowed by a language prefix.
- Tailwind only compiles classes it can see. The content globs cover `Views`,
  `Libraries`, `Config` and `Controllers` — a class emitted from PHP outside
  those is silently never built, which is a failure with no error message. The
  same applies to a class Tailwind *can* see but will not generate: an opacity
  modifier off the scale (`/98`, `/8`) produces nothing at all, and reads as
  correct in the markup. `bg-brand-black/98` shipped a full-screen navigation
  drawer with no background. `scripts/check-tailwind-classes.mjs` catches both.
- **The header's menu is a measurement, not a breakpoint** — the same eight
  items are half again as wide in Tamil, so `siteHeader.js` lays the nav out and
  asks whether it overflowed. Anything whose visibility depends on that answer
  must read the same `fits` state; a CSS breakpoint alongside it will disagree,
  and did: the drawer was `lg:hidden` while the button that opened it was not.

---

## Quick start (SQLite)

```bash
composer install
cp .env.example .env           # SQLite by default
php spark key:generate
php spark migrate --all
php spark db:seed "Modules\Core\Database\Seeds\DatabaseSeeder"

npm install
npm run build                  # assets into public/build
php spark serve --port 8083    # must match app.baseURL in .env
# open http://localhost:8083/  → the welcome page
```

The seeder prints the first administrator's password once, on creation. No
credentials ship in this repository.

Front-end dev with hot reload: `npm run dev` alongside `php spark serve`.

---

## Checks

Run against a site already serving on 8083:

```bash
node scripts/check-pages.mjs           # every page, 4 widths, 3 languages:
                                       # status, JS errors, sideways scroll,
                                       # invisible blocks, unresolved lang keys
node scripts/test-language-switch.mjs  # Clause 3.15's switching behaviour
node scripts/test-language-modal.mjs   # the first-visit language chooser
node scripts/test-navigation.mjs       # the menu is reachable at every width,
                                       # in every language — inline or drawer
node scripts/check-tailwind-classes.mjs # every utility written actually exists
node scripts/test-hero-slider.mjs      # the hero slideshow: advance, pause,
                                       # focus, reduced motion, a11y
node scripts/test-assistant.mjs        # the help assistant: focus, answers,
                                       # the no-answer path, all three languages
node scripts/check-hero-contrast.mjs   # hero text vs the pixels behind it
bash scripts/check-media-paths.sh      # every /media/ reference has a file
node scripts/screenshot.mjs <url> <out.png> [w] [h] [full]
node scripts/make-favicon.mjs          # re-render the icon set from the emblem
```

`check-hero-contrast.mjs` is the one to run after changing anything about the
hero. It hides the glyphs, photographs the panel and measures each run of text
against the pixels actually behind it — the only way to know that a photograph
nobody has uploaded yet will not quietly take the front page below AA.

---

## Deployment

`deploy/setup.sh` stands a site up on plain Nginx + PHP-FPM and only ever
*adds* one: its own directory, its own database, its own vhost. It refuses to
clobber a vhost it did not write, and never touches another site's database.

The `Deploy` workflow runs it over SSH from a GitHub runner. Dispatched from
the `tshda` branch its inputs default to this site, so re-deploying needs no
edits. Deploying a *second* site means changing `domain`, `app_dir` and
`db_name` together — leaving one behind points the new site at an existing
install's directory or database.

The first run serves plain HTTP, because naming a certificate that does not
exist yet makes `nginx -t` fail *after* the database has been created. Then:

1. `Server ops` → `setup-tls-certbot` for the domain.
2. Re-run `Deploy`, which installs the TLS vhost and sets `app.baseURL` to
   `https://`.

Secrets (`jwt.secret`, `encryption.key`, the database password) are generated
once and preserved across deploys — rotating them on every run would invalidate
every issued token and make everything already encrypted unreadable.

---

## Still to do before launch

- The Authority's own contact email address, and the telephone numbers for each
  regional office. The head office number and postal address are the published
  ones; the rest are blank rather than guessed, because a wrong number on a
  government contact page is worse than none.
- Named post-holders in the staff directory. The posts are seeded; the people
  are "To be confirmed" — inventing officials for a government site would be
  worse than an honest placeholder.
- The published statistics. The datasets carry their real shape and are marked
  as awaiting figures.
- SMTP details under Settings → Email, and reCAPTCHA keys under Settings →
  Security. Until both are set the site stores submissions without emailing and
  accepts forms without scoring them — which is what it does today.
- Photographs for the home page hero. Upload them to the Media library's `hero`
  folder — until then the panel shows the emblem rather than a stock picture of
  somebody else's tea garden.
- The cron line from `/admin/tasks`, pasted into the server's crontab.
- Sinhala and Tamil for the long editorial prose, once the English is signed off
  by the CMT. Every title, label and short summary is already translated.
