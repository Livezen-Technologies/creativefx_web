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

### Security

- Bypass keys are compared with `hash_equals`, and the bypass cookie is
  HttpOnly + SameSite=Lax.
- The offline page carries **no** `noindex`: 503 + `Retry-After` already tells
  crawlers the outage is temporary, while `noindex` would invite the
  deindexing the 503 exists to prevent.
- Sessions are only touched when a session cookie is already present, so an
  outage does not mint a session for every visitor and bot that hits the page.
