# Changelog

All notable changes to this project are recorded here, newest first.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
The site ships continuously rather than in numbered releases, so entries are
grouped by the date the work landed. Anything before the first entry below
lives only in the git history.

---

## 2026-08-17

### Added

- **Maintenance mode** — a switch that takes the public site off the air and
  answers every visitor with a branded offline page and an HTTP 503, without
  touching Nginx or redeploying.
  - `Modules\Core\Libraries\Maintenance` — the switch. Three levers, read in
    order: `maintenance.enabled` in `.env`, then `writable/maintenance.flag`
    (works with the database down), then the `maintenance.enabled` settings
    row. Turning it on or off writes the file *and* the row, so the two never
    disagree.
  - `Modules\Core\Filters\MaintenanceFilter` — registered in
    `Config\Filters::$required` so it runs before routing: URLs matching no
    route return the offline page rather than leaking the 404 page.
  - Offline page (`modules/Core/Views/maintenance.php`) — standalone by
    design: no layout, no Vite bundle, no webfonts, nothing to fetch, so it
    draws even when the asset build or the database is broken. Ships copy in
    every site language, picked from `?lang=`, the URL, or the locale cookie.
  - **Admin → Maintenance** — on/off switch, the copy shown to visitors, an
    IP allowlist, the `Retry-After` value, and a shareable preview link. A
    banner on every admin screen shows when the site is dark.
  - **Preview links** — `?preview=<key>` swaps the key for a cookie and
    redirects to a clean URL, so a client or colleague can browse the real
    site during the outage without the key trailing through history and
    referrers.
  - `php spark maintenance on|off|status` — the same switch from the server,
    with `--headline`, `--message` and `--until`.
- **Services registry** — what CreativeFX sells is now held in one place, the
  `services` table, instead of being retyped into each template. The header's
  Services dropdown, the footer, the Services overview grid and the
  related-service links all read from it, so renaming a service or reordering
  the list updates every one of them at once.
  - `Modules\Services\Models\ServiceModel` — `published()` for the ordered
    list, `findPublishedBySlug()` for a single service, and `related()` for
    the "explore more" strip at the foot of a service page.
  - `ServiceSeeder` seeds the six launch services — Photography &
    Videography, Podcast Studio, Live Streaming, Gear Renting, Social Media
    Advertising, Digital Marketing — each with a one-line tagline, a
    two-sentence summary and placeholder card and hero art. It inserts by
    slug and never updates, so wording and ordering changed in Admin survive
    the next deploy.
  - The long-form copy for a service stays an ordinary CMS page under the
    slug `services/<slug>`. The registry holds only the short text that
    repeats across the site.
- **Portfolio** — projects and categories, a filterable listing at
  `/{locale}/portfolio` and a case-study page at `/{locale}/portfolio/{slug}`
  carrying the client, industry, service, date, challenge, approach, gallery
  and results. Category filtering works as plain links (so it survives with
  JavaScript off) and is enhanced to filter without a reload when JS is on.
- **Gear rental** — a rentable equipment catalogue with categories, daily and
  weekly LKR rates, specs and an availability state, surfaced through a
  `gear_grid` block on the Gear Renting service page.
  - Seeded with nine kit families — Cameras, Lenses, Lighting, Audio,
    Tripods, Gimbals, Drones, Streaming, Studio — and the fourteen items that
    go out most often, each with a starting Colombo day and week rate. It
    inserts by slug and never updates, so rates and wording changed in Admin
    survive the next deploy.
  - Every card shows an availability state — Available, Booked or
    Maintenance — that a coordinator flips per item, separately from whether
    the item is published at all. Booked kit stays on the page: the card says
    it is out, and the enquiry is still worth making for another date.
  - **Rent Now** carries the item into the quote form
    (`/{locale}/quote?service=gear-renting&item=<slug>`), so a rental
    enquiry arrives already naming the kit it is about.
- **Quote requests** — a six-step request flow at `/{locale}/quote`, the site's
  primary conversion path. It writes into the existing lead inbox with the new
  service, project type, budget, preferred date, location and attachment
  fields, and the lead statuses now run new → contacted → qualified →
  proposal sent → negotiation → won/lost.
- **Seven new content blocks**, available in the page builder the moment they
  exist (the admin discovers block types by globbing the block directory):
  `services_grid`, `testimonials` (carousel), `faq` (native `<details>`
  accordion that also emits FAQPage structured data), `packages` (pricing
  cards), `service_features`, `team_grid` and `logo_wall`.
- **The CreativeFX site content** — every public page is now written and
  seeded: the home page, Our Story, the Services overview and a full page for
  each of the six services (`/{locale}/services/<slug>`), plus Contact. Each
  service page carries an overview, a What We Offer grid, the six production
  steps, four pricing tiers in LKR, an FAQ and a CTA into the quote flow; Gear
  Renting also shows the live rental catalogue. Run it with `php spark db:seed
  "Modules\Core\Database\Seeds\DatabaseSeeder"`, and edit any of it afterwards
  in Admin → Pages.
  - The home page is the row with the **empty slug** and it is the only page
    flagged `is_home`; seeding it clears the flag from Norlanka's old `home`
    page, which would otherwise still decide what `/en` shows.
  - Re-seeding **replaces** the sections and blocks of those ten pages, so
    copy changed in Admin is overwritten. Everything else the seeder touches
    (services, portfolio, gear) still inserts by slug and is left alone.
- **Admin screens for the new CreativeFX content** — Services, Portfolio
  Projects, Portfolio Categories, Gear Items and Gear Categories are now
  editable in the admin console at `/admin/services`,
  `/admin/portfolio-projects`, `/admin/portfolio-categories`,
  `/admin/gear-items` and `/admin/gear-categories`. Projects and gear pick
  their category from a dropdown of the real categories, rates are left empty
  for kit that is quoted on request, and a gear item's day-to-day
  availability (available / booked / in maintenance) is a separate switch from
  whether it is published at all.
- **Quote request flow** at `/{locale}/quote` — the site's primary conversion
  path, and where every "Get a Quote" and "Rent Now" button now lands.
  - Six steps: service, project details, date and location, budget, contact
    details, then submit. It is presented as a wizard with a progress bar, but
    it is one ordinary form: with JavaScript off the whole thing renders as a
    single page that still submits, and the server validates either way.
  - A link can pre-fill it — `?service=<slug>` pre-selects the service and
    `?item=<gear-slug>` opens the brief with the kit named, so a click from a
    service page or a gear card arrives already answered.
  - Requests land in **Admin → Quote Requests** as leads with the source
    `quote-form`, alongside the service, project type, budget band, preferred
    date, location and the language the request came in on. The visitor gets a
    reference number (`CFX-00123`) on the confirmation page.
  - Briefs and reference files (PDF, JPG, PNG or ZIP, up to 8MB) are optional
    and are stored under `writable/uploads/quotes/` with a random filename —
    never in a web-served directory, so they are only reachable through the
    admin panel.
  - Budget bands are quoted in LKR: under 100,000 / 100,000–300,000 /
    300,000–750,000 / 750,000–1.5M / over 1.5M / not sure yet.
- **Worksuite chat widget** on every public page, loaded deferred at the end
  of the body so it never blocks rendering. It is a third-party script from
  `app.worksuite.lk`; the admin console and the offline page do not load it.
- **Search-engine essentials the site never had**: a canonical URL and
  `hreflang` alternates on every page, Organization structured data, a
  generated `/sitemap.xml` that pairs each page's translations, and a
  `robots.txt` that keeps `/admin` and `/api` out of the index.
- **Sinhala and Tamil** join English. Noto Sans Sinhala/Tamil are bundled
  behind the brand faces, so Latin still sets in K2D and Avenir and only the
  glyphs those faces lack fall through to Noto.
- Placeholder artwork: 53 generated brand panels under
  `public/media/placeholders/`, written by `scripts/gen-placeholders.mjs`.
  They are abstract by design — a page still showing one reads as
  art-directed rather than unfinished, and no stock photo can ship by
  accident.
- `scripts/shots.mjs` — screenshots any page list at desktop or mobile widths
  for design review, with the reveal animations settled so sections do not
  photograph blank.
- `CHANGELOG.md` (this file) and `CLAUDE.md`.

### Changed

- **The brand accent is now the logo's amber (`#FFC107`), not red.** Every
  button, link, eyebrow, statistic, hover state, focus ring and chart accent
  moved with it, on the public site and in the admin console, because the
  accent is a single design token.
  - Text on a filled accent surface is now a token too (`--accent-ink`). It
    had to be: white on the old red passed contrast, but white on amber is
    about 1.8:1 and fails outright. Filled buttons now use near-black — 11.6:1
    — and the Impact page's green scope keeps its white.
  - **The real logo artwork is in.** The header and footer now show the
    supplied `cfx-wordmark.svg` rather than the wordmark set as text, and the
    symbol, the three favicon sizes and `favicon.ico` are the real mark. (The
    earlier type treatment — bold, tightly tracked `CREATIVE` + `FX` — was an
    approximation standing in until the artwork arrived; it is superseded.)
  - The brand mark, favicons, the offline page and the 53 placeholder panels
    were all regenerated in amber. `scripts/gen-brand.mjs` rebuilds every
    favicon size from one source file, so replacing the logo is one command.
- **Page heroes can fill the viewport.** Set `fullscreen` on a `pagehero`
  block and it opens full-height with a scroll cue, the treatment a film hero
  always had; leave it off and the hero stays its compact self. The backdrop —
  film, still or aurora — now drifts gently as the hero scrolls away.
- `deploy/go.sh` — one command that deploys the site. It detects whether the
  domain is already installed by reading its nginx vhost, dumps the database
  before touching anything, and then runs the first-install or the update path
  as appropriate. It refuses to reset a checkout that belongs to another
  project, and prints what it will do before doing it.
- **The deploy targets `magiccorn.livezencloud.com`.** Every default in
  `deploy/setup.sh`, `deploy/update.sh` and the Deploy workflow now points at
  the CreativeFX host, database (`magiccorn_prod`) and install directory
  (`/var/www/magiccorn`) instead of Norlanka's — so running any of them
  without arguments provisions the right site rather than the wrong one. The
  server-generated `app.baseURL` follows the domain automatically.
- **The site is now CreativeFX, not Norlanka.** The name, wordmark, mark,
  favicons, preloader and social image all change on deploy, and a data
  migration rewrites the `settings` rows that carry the brand — but only where
  they still hold the Norlanka value, so anything already corrected by hand
  survives.
- **Norlanka's phone and WhatsApp numbers are cleared**, not carried over. The
  header and footer hide a contact link until its setting is filled in, so an
  empty value is a working state; set the real numbers in Admin → Settings.
- **Navigation is rebuilt** around Home / Our Story / Services / Portfolio /
  Contact, with a Services dropdown driven by the services registry, a Get a
  Quote action and a WhatsApp shortcut. The footer gains Services, Follow and
  newsletter columns.
- **The home page is now editable.** It used to be a hand-written nine-slide
  fullscreen deck that ignored the CMS entirely; it now renders through the
  same block pipeline as every other page, so every section of it can be
  changed in Admin → Pages → Home.
- **The apparel sections are retired**: the 3D showroom, careers portal,
  newsroom, product catalog and ESG dashboards are gone from the site
  navigation, their routes are commented out and their pages are unpublished
  (they were still reachable by slug through the CMS catch-all). No module,
  table or row was deleted — restoring a section is uncommenting its route and
  republishing its page.
- **A lead now moves through a real sales pipeline**: new → contacted →
  qualified → proposal sent → negotiation → won or lost, replacing
  new/qualified/converted/lost. Existing leads are not rewritten, so anything
  still filed as "converted" keeps that wording until someone re-files it by
  hand on the lead's own screen.
- **Locales are `en`/`si`/`ta`**, replacing `en`/`ja`/`es`/`zh`. The Japanese,
  Spanish and Chinese language files are still on disk but no longer routable.
  The Translation Manager seeds a locale with no file of its own from English,
  so translators start from readable copy instead of blank fields.

- The public site is **offline as of this deploy**: the
  `EnableMaintenanceMode` migration sets `maintenance.enabled` to `1`, so
  `deploy/update.sh` takes the site down on its migration step. Bring it back
  from Admin → Maintenance, with `php spark maintenance off`, or by rolling
  the migration back.
- `SettingSeeder` seeds the `maintenance` settings group; fresh installs come
  up live.

### Fixed

- **The new content blocks now sit flush with the rest of the page.** The
  Testimonials block was taller than every other section (it padded itself
  py-20 against the house py-16) and drew its focus outline at a wider corner
  radius than the card inside it; the Logo Wall's plates were packed tighter
  than every other grid on the site and its placeholder wordmark plates had
  squarer corners than the plates carrying real artwork. Editors dropping
  Services, Testimonials, FAQ, Packages, What We Offer, Team and Logo Wall onto
  one page now get one consistent rhythm down the page.
- **Nothing in the new blocks can push the page sideways on a phone.** A long
  FAQ question, a long feature line in a Packages tier, or a Testimonials block
  with many quotes could each widen the page past the screen on a 360px handset
  and leave the whole site scrolling horizontally. Questions and feature lines
  now wrap, and the testimonial dots wrap to a second row.
- **The statistics on the home page read zero for anyone using reduced
  motion.** Counters start at 0 in the markup and are counted up by script,
  and the reduced-motion path skipped the count entirely — so a visitor who
  asks their system for less animation saw "0+ projects completed". Reduced
  motion now means the number is not animated, not that it is never shown.
- **Page heroes with a still image showed a plain gradient.** `pagehero` read
  its image only as the poster attribute of a background video, so every page
  without a film — which is all of them today — dropped its hero art. A still
  now paints behind the title with the same scrims the video branch uses.
- **The footer newsletter posted into the quote form.** A signup supplies
  nothing but an email, so it failed the quote validator and bounced the
  subscriber to the quote page with a list of errors about fields the footer
  never asked for. Signups now have their own endpoint, land in the lead inbox
  tagged `newsletter`, and say thank you in place.
- **The Services menu closed itself when clicked.** Hover opened the panel and
  the click that followed toggled it shut. Services is now a link to the
  overview page; hover and keyboard focus open the panel, Escape closes it.
- **Portrait project covers filled the screen**, pushing the case study below
  the fold. Cover height is capped.
- **Client logo placeholders rendered as black squares** on the logo wall's
  light plates, and **team cards without social links** sat their names lower
  than the rest of the grid.
- The offline page still carried the old Norlanka mark and favicon.

### Security

- Bypass keys are compared with `hash_equals`, and the bypass cookie is
  HttpOnly + SameSite=Lax.
- The offline page carries **no** `noindex`: 503 + `Retry-After` already tells
  crawlers the outage is temporary, while `noindex` would invite the
  deindexing the 503 exists to prevent.
- Sessions are only touched when a session cookie is already present, so an
  outage does not mint a session for every visitor and bot that hits the page.
