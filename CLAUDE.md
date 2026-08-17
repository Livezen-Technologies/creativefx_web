# Working in this repository

## Changelog — always update it

`CHANGELOG.md` is part of every change. Before finishing any piece of work,
add or extend an entry for it:

- Group entries under a `## YYYY-MM-DD` heading for the day the work lands;
  add to the existing heading if one is already there for today.
- Use the Keep a Changelog sections — **Added**, **Changed**, **Fixed**,
  **Removed**, **Security** — and only the ones that apply.
- Write what changed for the people running the site, not a restatement of
  the diff. Name the screen, the command or the URL they will touch.
- Anything that changes the live site's behaviour on deploy (a data
  migration, a default flipped, content replaced) belongs under **Changed**,
  said plainly.

## Conventions worth knowing

- Domain code lives in namespaced modules under `modules/`; `app/` is only the
  global shell. Register new modules in `app/Config/Autoload.php`.
- Migrations only run with `php spark migrate --all` (module migrations are
  skipped otherwise). Filenames carry a global timestamp prefix so cross-module
  foreign keys resolve in order.
- Seeders insert with `ignore()` — they never overwrite an existing row, so use
  a data migration when an existing install needs a value changed.
- Translatable columns hold JSON locale maps; read them through `t_field()`,
  which falls back to `en`.
- Settings live in the `settings` table, read with `setting($key, $default,
  $group)`. The helper caches per request and swallows database errors.
- The locale filter alias is `applocale`, not `locale` — PHP's built-in
  `\Locale` class would shadow it.
- Run the suite with `vendor/bin/phpunit --no-coverage`. Tests use the `tests`
  database group, which is intentionally not provisioned, so anything reading
  `settings` in a test gets defaults.
