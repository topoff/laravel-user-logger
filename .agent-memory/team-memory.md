# Team Memory: laravel-user-logger

This file contains deliberate, team-reviewed memory for this project.
It is versioned in this repository and is intended for Claude Code, Codex, and OpenCode.

## Stable facts

- Request/user logging package: sessions, logs, devices, agents, referers, languages, domains on a dedicated `user-logger` DB connection, plus Pennant-based experiment measurement and optional performance logs.
- Consumed via composer git tags by host apps (`backend/`, `landingpages/`). **Every release commit gets a SemVer tag** (`vX.Y.Z`) — untagged commits are invisible to consumers. Docs-only commits may stay untagged and ride with the next release.
- PHP `^8.4`, illuminate `^12||^13`, Pest 4, PHPStan level 5 (larastan), Rector + Pint.
- The logger is disabled in console and in the `testing` environment. Tests enable it via `user-logger.enabled` + `user-logger.enabled_in_testing` (no reflection hacks).
- Pennant integration registers **only** the package's own named store (`user-logger`). Never override the host app's default `database` store.

## Architecture pointers

- Boot flow: `InjectUserLogger` middleware (web group) → `UserLogger::boot()` → repository `findOrCreate`s. Errors are suppressed outside `app.debug` — a broken logger must never break the host app.
- All lookup tables carry unique constraints; `referers` and `agents` dedupe via a `lookup_hash` (sha1 over the truncated attributes; pre-migration rows keep NULL, deliberately no backfill).
- Client-controlled input (UTM params, headers, URIs, hosts) is truncated to DB column limits via `Support\AttributeLimiter` **before** hashing/insert.
- IPs: HMAC-SHA256 via `Support\IpHasher` (key `ip_salt`, fallback `app.key`); `hash_ip=false` stores plain text — then schedule `user-logger:prune-ips` (`retention.ip_days`).
- Retention: `Log`, `Session`, `PerformanceLog` are `MassPrunable` (opt-in via `retention.*` / `performance.retention_days`), pruned via `model:prune`. Sessions prune only when they have no remaining logs.
- Per-process caches: snowplow `JsonConfigReader` (119KB json), `CrawlerDetect` instance. `UserLogger` is a **scoped** (not singleton) binding — keep it Octane-safe.
- Referer detection: snowplow matching code + **own bundled database** `resources/data/referers.json`, generated from `matomo/searchengine-and-social-list` (search/social/**ai** mediums, email carried over from snowplow). Refresh is automated: monthly GitHub Action (commits + patch-tags on change), `post-update-cmd` regenerates on every local `composer update`, manual via `composer update-referers`.

## Commands and workflows

- Tests: `composer test` (Pest). Full toolchain before finalizing: `composer clean` (Rector + Pint + PHPStan).
- Release: commit → `git tag vX.Y.Z` → `git push origin master && git push origin vX.Y.Z`.
- Artisan: `user-logger:flush` (asks for confirmation, `--force` for cron), `user-logger:haship`, `user-logger:prune-ips [--days=N]`.

## Conventions specific to this project

- New `env()` calls in `config/user-logger.php` need a count bump in `phpstan-baseline.neon` (known larastan false positive for package config files).
- Migrations: named classes extending `Support\Migration` (connection `user-logger`), guarded with `hasTable`/`hasColumn` checks.
- `tests/TestCase.php` builds the schema manually — schema changes must be mirrored there.
- New PHP files use `declare(strict_types=1);`.
- Schema changes ship as package migrations — never DDL at runtime in the service provider.

## Do not store here

- Personal preferences.
- Temporary debugging notes.
- Secrets, credentials, API keys, tokens, or private customer data.
- Facts that belong only to another project.
- Long-form agent documentation that belongs in `docs/`.
