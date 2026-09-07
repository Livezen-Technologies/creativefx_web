# MyLearnPlus — Adobe Creative Cloud and AI training

The public site, catalogue, checkout and learner area for **MyLearnPlus**, a
training school operated by Livezen Technologies: instructor-led and self-paced
courses in Adobe Creative Cloud and generative AI, sold in USD globally and in
LKR in Sri Lanka.

Built to the MyLearnPlus platform blueprint v1.0 (6 September 2026). Where a
decision in the code answers a section of that document, the comment says which.

---

## The one place this differs from the blueprint, and why

Blueprint §5.1 specifies **Next.js for the front end with a headless CodeIgniter
API behind it**. This build is a **CodeIgniter 4.7 monolith** rendering its own
views, on the platform the site was cloned from.

That was the instruction — clone the existing site — and a Next.js rewrite is
not a clone: it discards the layout, the block CMS, the admin console, the theme
tokens, the locale machinery and the whole verification suite, and rebuilds them
in a second language and a second runtime. What the blueprint actually asks for
in §3, §4, §6, §7, §8 and §11 — the product — is built. §5.1's topology is not.

| Blueprint | Here | Consequence |
|---|---|---|
| Next.js + ISR | CI4 server-rendered views | Same crawlability; caching is CI4's, not ISR's |
| Meilisearch | `Modules\Site\Libraries\SiteSearch` (SQL) | One service fewer to run; the seam is one class wide |
| Mux / Bunny signed playback | `lessons.video_provider` + `video_ref` | A provider adapter drops in without a schema change |
| `/api/v1` on the render path | kept for future headless use, not used to render | Nothing lost |
| `CF-IPCountry` | read when present, else `Accept-Language`, else the default book | Cloudflare is in front, so it usually is present |

---

## What is here

| Blueprint | What it asks for | Where it lives |
|---|---|---|
| §3.1 | The category tree — Adobe, AI, Design & Digital | `course_categories`, `CatalogSeeder::categories()` |
| §3.2 | The course entity and every required attribute | `courses` + its eight child tables, `Modules\Catalog` |
| §3.3 | Two price books, published never converted | `Modules\Commerce\Services\PricingService` |
| §4 | The information architecture | `modules/Site/Config/Routes.php` |
| §4.1 | The home page section order | `Modules\Site\Controllers\Home`, `Views/home/index.php` |
| §4.2 | The course page, with the mode-switching booking panel | `Views/courses/show.php`, `partials/booking_panel.php` |
| §5.3.1 | Transactional seat inventory | `Modules\Commerce\Services\InventoryService` |
| §5.3.2 | Money as integer minor units | every `*_cents` column; no floats anywhere |
| §5.3.3 | Payment is webhook-authoritative | `Modules\Learning\Services\EnrolmentService::fulfil()` |
| §5.3.5 | Idempotency on orders and webhooks | `orders.idempotency_key`, unique `(gateway, gateway_ref)` |
| §6 | The data model | four migrations under `modules/{Catalog,Commerce,Learning,Account}` |
| §7.1 | Booking and checkout, with attendees per seat | `Modules\Commerce\Controllers\Checkout` |
| §7.2 | Cancellation, transfer and retake, built not just written | `RescheduleRequestModel`, `EnrolmentService::cancelSession()` |
| §7.3 | The self-paced experience | `Modules\Learning\Controllers\Player` |
| §7.4 | Certificates and public verification | `CertificateService`, `/verify/{code}` |
| §7.5 | The corporate funnel: enquiry → quote → invoice | `Modules\Commerce\Controllers\Corporate`, `training_leads`, `quotes` |
| §7.6 | Multi-currency and geo | `PricingService`, `price_books`, `Controllers\Currency` |
| §8.1 | Course / CourseInstance / Event / FAQPage JSON-LD, split sitemaps | `Modules\Catalog\Libraries\Schema`, `Controllers\Sitemap` |
| §10 | WCAG 2.2 AA, en + si, rate limiting, CSRF | throughout; see Checks |
| §11 | The role model | `Modules\Auth\Database\Seeds\RoleSeeder` |

---

## Tech stack

| Layer | Tech |
|---|---|
| Backend | CodeIgniter 4.7.4, PHP 8.3+, MySQL 8 in production (SQLite for local dev) |
| Frontend | Tailwind CSS 3.4, Alpine.js, GSAP, Lenis, Swiper, Vite |
| Documents | dompdf (certificates, invoices), endroid/qr-code (verification codes) |
| Type | Noto Sans + Noto Sans Sinhala, self-hosted |
| Infra | Nginx + PHP-FPM. No Node runtime in production — assets are built at deploy time |

Palette: `#3F35C7` indigo and `#92400E` amber, both measured against WCAG 2.2 AA
on the light and the dark grounds. Locales: `en`, `si`.

---

## Modular architecture

```
modules/
  Core/        layout, partials, LocaleFilter, settings, mailer, scheduler
  Site/        public shell: home, CMS renderer, search, sitemaps, 404
  Catalog/     courses, categories, sessions, instructors, venues, bundles,
               reviews, resources, webinars, JSON-LD
  Commerce/    currencies and price books, cart, checkout, orders, payments,
               gateways, coupons, corporate leads and quotes
  Learning/    enrolments, attendance, lessons, quizzes, progress,
               certificates, transfers
  Account/     learner registration, sign-in, verification, the account area
  Cms/         pages / sections / blocks, menus, per-page SEO
  Auth/        users, roles, permissions, JWT
  Admin/       the console
  Translation/ the translations table and its manager
  Media/       the media library
  News/        the blog
  Careers/     the school's own vacancies
  Crm/         contact messages
  Video/       video records with audio tracks and subtitles
  Analytics/   self-hosted page-view capture and reporting
```

Namespaces are registered in `app/Config/Autoload.php`. **A module directory does
not exist until it is in that list** — nothing in it autoloads, its migrations are
not discovered, and there is no error anywhere.

---

## Conventions

- **Translatable content is a JSON locale map** (`{"en":…,"si":…}`) resolved at
  render time by `t_field()`, which falls back to English. A course can go live
  in English while the Sinhala is still being written.
- **A person's name is not translatable; their headline is.** The same rule
  decides every column.
- **All public routes live in `modules/Site/Config/Routes.php`,** in one order,
  because the CMS catch-all `(:locale)/(:segment)` at the bottom swallows
  anything declared after it and serves a 404 for a page that exists.
- **Money is an integer of minor units plus a currency code**, at every layer.
  `money($cents, $currency)` is the only division by 100 on the site. Prices are
  published per currency and **never converted at runtime** — there is no
  exchange rate anywhere in the code. `PricingService::REPORTING_LKR_PER_USD`
  exists only so a revenue report can show one total.
- **Seat inventory is transactional.** `seats_sold` and `seats_reserved` are
  written only by `InventoryService`, inside a transaction that takes the write
  lock on the session row first — `FOR UPDATE` on MySQL, and a deliberate no-op
  UPDATE on SQLite, which takes its write lock when a write is issued rather
  than when a transaction opens. A plain SELECT inside a transaction is not a
  lock there, and code that assumes it is passes every single-threaded test and
  oversells under load.
- **Payment is webhook-authoritative.** The browser returning from a gateway
  proves nothing. An order becomes paid in `EnrolmentService::fulfil()`, called
  by a verified webhook or by an administrator recording a bank transfer.
- **A gateway with no keys is not offered.** No half-configured payment routes:
  the button is there and works, or it is not there. Bank transfer always works,
  so the school can take a booking before any merchant account exists.
- **Seeders never overwrite editorial work.** Courses and bundles upsert by slug
  and skip any row whose `is_custom` flag an admin save has set; list tables seed
  only while empty; posts stop at a row whose `updated_at` differs from its
  `created_at`.
- **Nothing invented is ever seeded.** No testimonials, no ratings, no learner
  counts, no client logos, no named trainers, no telephone numbers, no street
  addresses. The reviews page says the school is new; the faculty profiles are
  flagged `is_placeholder` and are deliberately excluded from `Person` structured
  data; the home page's credibility bar counts rows in the database and omits any
  figure it cannot derive.
- **`AggregateRating` is emitted only when `rating_count > 0`.** A rating nobody
  left is a fabrication in a rich snippet.
- **CSRF is global**, declared in `$globals` with `webhooks/*` and `api/*`
  excepted — `$filters` silently ignores an `except` key, which is why it is not
  declared there.
- **`php spark migrate --all`** — module migrations only run with `--all`.
- The locale filter alias is `applocale`, not `locale`, which collides with PHP's
  case-insensitive built-in `\Locale`. Route groups that gate on something else
  must list **both** filters, or the pages inside them render in whatever
  language `Accept-Language` negotiated rather than the one in the URL.
- Tailwind only compiles classes it can see, and silently generates nothing for
  an opacity modifier off its scale (`/98`, `/8`). `scripts/check-tailwind-classes.mjs`
  catches both. `white` and `brand-black` are themeable tokens, not colours:
  `text-white` is near-black on the light theme.
- **The logo is drawn, not fetched** — inlined from `--accent`, `--gold` and
  `--fg`, so there is no request, no aspect ratio to declare wrongly, and a theme
  switch recolours it in the same frame as everything else.

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
# Workers matter. `php -S` is single-process by default, so a page requesting
# its own assets can deadlock it: the browser holds the connection open waiting
# for a stylesheet the server cannot begin serving until the page finishes. It
# presents as every Playwright check timing out on navigation while curl still
# answers 200 — which is a confusing hour if you have not seen it before.
PHP_CLI_SERVER_WORKERS=6 php -S 127.0.0.1:8083 -t public/ \
    vendor/codeigniter4/framework/system/rewrite.php
# open http://localhost:8083/
```

The seeder prints the first administrator's password once, on creation. No
credentials ship in this repository.

Front-end dev with hot reload: `npm run dev` alongside the server above.

---

## Checks

Run against a site already serving on 8083:

```bash
node scripts/check-pages.mjs            # every page, 4 widths, both languages:
                                        # status, JS errors, sideways scroll,
                                        # invisible blocks, unresolved lang keys
node scripts/check-tailwind-classes.mjs # every utility written actually exists
node scripts/test-navigation.mjs        # the menu is reachable at every width
node scripts/test-splash.mjs            # the loading screen: centring and cost
node scripts/test-language-switch.mjs   # switching keeps you on the page
node scripts/test-language-modal.mjs    # the first-visit chooser
node scripts/test-course-page.mjs       # the money page: modes, dates, price
node scripts/test-booking.mjs           # course → basket → checkout, and the
                                        # seat count somebody else sees
node scripts/test-pricing.mjs           # country → price book → the price shown
node scripts/check-structured-data.mjs  # every JSON-LD block, and the two
                                        # claims that must never be fabricated
node scripts/test-assistant.mjs         # the help assistant, including no-answer
php   spark check:placeholders          # no draft "{to be confirmed}" reaches
                                        # a reader, in the seeds or the database
php   scripts/check-language-keys.php   # every lang() key resolves, and no
                                        # bundle declares one key twice
node scripts/check-hero-contrast.mjs    # hero text against its own background
node scripts/test-seat-inventory.mjs    # eight processes, one seat
                                        # (--prove shows the check bites)
bash  scripts/check-media-paths.sh      # every /media/ reference has a file
node scripts/screenshot.mjs <url> <out.png> [w] [h] [full]
```

**Every check must be negative-tested before it is trusted**: break the thing,
watch it fail, put it back. This codebase has shipped a test that passed against
the bug it was written for.

---

## Deployment

`deploy/setup.sh` stands a site up on plain Nginx + PHP-FPM and only ever *adds*
one: its own directory, its own database, its own vhost. It refuses to clobber a
vhost it did not write, and never touches another site's database.

The `Deploy` workflow runs it over SSH from a GitHub runner. Its inputs default
to this site, so re-deploying needs no edits. Deploying a *different* site means
changing `domain`, `app_dir` and `db_name` together — leaving one behind points
the new site at this install's directory or database.

The first run serves plain HTTP, because naming a certificate that does not exist
yet makes `nginx -t` fail *after* the database has been created. Then:

1. `Server ops` → `setup-tls-certbot` for the domain.
2. Re-run `Deploy`, which installs the TLS vhost and sets `app.baseURL` to `https://`.

Secrets (`jwt.secret`, `encryption.key`, the database password) are generated
once and preserved across deploys — rotating them on every run would invalidate
every issued token and make everything already encrypted unreadable.

---

## Still to do before launch

These are the things this build deliberately did **not** invent. Every one of
them is a place where a plausible-looking placeholder would have been worse than
an obvious gap.

**Commercial**
- **Final prices.** The catalogue is priced from the blueprint's own published
  bands (§3.3) and is provisional. Set the real numbers in Admin → Dates & seats,
  or per band in `CatalogSeeder::BANDS` before the first seed.
- **Merchant accounts.** Stripe, PayHere and PayPal all ship unconfigured, so
  the site currently offers bank transfer only. Add keys under Settings →
  Payments and each button appears. Start the onboarding early: it is the item
  most likely to hold up a launch.
- **Bank transfer instructions** (Settings → Payments) — the account details a
  buyer is asked to pay into. Blank until you set them.
- **Tax.** A Sri Lankan VAT rule is seeded **inactive at 0%**. Whether VAT
  applies to training services, and at what rate, is a question for the school's
  accountant. Charging the wrong tax is worse than charging none and correcting
  it before launch.

**Content and identity**
- **Named instructors.** The four faculty profiles are honest placeholders
  flagged `is_placeholder`, and are excluded from `Person` structured data while
  that flag is set. Replace them with real trainers, photographs and credentials,
  then clear the flag.
- **Telephone number and street address** (Settings → Contact, and the venue
  records). Blank rather than guessed. The location pages omit the address block
  entirely until one is set.
- **Reviews.** None are seeded and none should be. They appear as learners leave
  them, and only from somebody holding an enrolment on that course.
- **The credibility figures** the home page cannot derive — learners trained,
  client logos, case studies. Nothing is shown until there is a record to count.
- **Course photography.** Course cards render without a hero image; upload them
  to the media library and set them per course.
- **Sinhala.** The interface is translated; the long editorial prose is not, and
  falls back to English. Add it in Admin → Translations once the English is
  signed off. Do not machine-translate a commercial page.

**Operational**
- **SMTP** (Settings → Email) and **reCAPTCHA** (Settings → Security). Until both
  are set the site stores enquiries without emailing and accepts forms without
  scoring them — which is deliberate: losing a booking to an unreachable mail
  server is worse than a missed notification.
- **The cron line** from `/admin/tasks`, pasted into the server's crontab. It
  releases expired seat holds and sends class reminders.
- **Change the administrator password** the seeder printed, and rotate the
  server password used to deploy.
