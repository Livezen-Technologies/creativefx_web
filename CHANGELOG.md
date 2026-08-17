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
    all four site languages, picked from `?lang=`, the URL, or the locale
    cookie.
  - **Admin → Maintenance** — on/off switch, the copy shown to visitors, an
    IP allowlist, the `Retry-After` value, and a shareable preview link. A
    banner on every admin screen shows when the site is dark.
  - **Preview links** — `?preview=<key>` swaps the key for a cookie and
    redirects to a clean URL, so a client or colleague can browse the real
    site during the outage without the key trailing through history and
    referrers.
  - `php spark maintenance on|off|status` — the same switch from the server,
    with `--headline`, `--message` and `--until`.
- `CHANGELOG.md` (this file) and `CLAUDE.md`.

### Changed

- The public site is **offline as of this deploy**: the
  `EnableMaintenanceMode` migration sets `maintenance.enabled` to `1`, so
  `deploy/update.sh` takes the site down on its migration step. Bring it back
  from Admin → Maintenance, with `php spark maintenance off`, or by rolling
  the migration back.
- `SettingSeeder` seeds the `maintenance` settings group; fresh installs come
  up live.

### Security

- Bypass keys are compared with `hash_equals`, and the bypass cookie is
  HttpOnly + SameSite=Lax.
- The offline page carries **no** `noindex`: 503 + `Retry-After` already tells
  crawlers the outage is temporary, while `noindex` would invite the
  deindexing the 503 exists to prevent.
- Sessions are only touched when a session cookie is already present, so an
  outage does not mint a session for every visitor and bot that hits the page.
