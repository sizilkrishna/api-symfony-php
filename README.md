# api-symfony-php

Symfony 7 REST API for the art catalogue, backed by PostgreSQL 12+.

Read endpoints for artworks and their taxonomies (author, form, location, school, timeframe, type), PostgreSQL full-text search, random picks, and a small query log. See [`docs/openapi.yaml`](docs/openapi.yaml) for the full contract.

## Quick start (Docker)

```bash
docker compose up --build
curl http://localhost:8080/api/health/ready
```

The `app` container applies migrations on start (`RUN_MIGRATIONS=1`). The database starts empty: load your data, then run `php bin/console app:db:feature-images`.

## Local development

```bash
composer install
printf 'APP_ENV=dev\nDATABASE_URL="postgresql://USER:PASS@127.0.0.1:5432/mgoart"\n' > .env.local
php bin/console app:db:migrate
php -S 127.0.0.1:8000 -t public
```

Requires PHP 8.2+ with `pdo_pgsql` and `mbstring`.

## Configuration

Set as real environment variables in production; `.env` only holds non-secret defaults.

| Variable | Default | Purpose |
|---|---|---|
| `APP_ENV` | `prod` | `prod` / `dev` / `test` |
| `APP_SECRET` | – | Random string |
| `DATABASE_URL` | – | `postgresql://user:pass@host:5432/db` (URL-encode special characters) |
| `DB_STATEMENT_TIMEOUT_MS` | `5000` | Abort any SQL statement after this long (`0` = off) |
| `API_KEY_CASE` | `lower` | `lower` → `title`, `upper` → `TITLE` in JSON records |
| `LOGS_API_KEY` | empty | Secret for `GET /api/logs` (`X-API-Key` header). Empty = endpoint disabled (404) |
| `RATE_LIMIT_ENABLED` | `1` | Per-IP rate limiting |
| `RATE_LIMIT_API_PER_MINUTE` | `120` | All `/api` routes except health |
| `RATE_LIMIT_LOGGER_PER_MINUTE` | `20` | `POST /api/logger` |
| `TRUSTED_PROXIES` | empty | Proxy IPs/CIDRs allowed to set `X-Forwarded-*`. **Set this behind a load balancer**, otherwise every client shares the proxy's IP for rate limiting and logs |
| `CORS_ALLOW_ORIGIN` | localhost only | Regex of allowed browser origins |

## Endpoints

| Route | Notes |
|---|---|
| `GET /api/art/all`, `/api/art/all/{id}` | Artworks |
| `GET /api/art/{author\|form\|location\|school\|timeframe\|type}/{id}` | Artworks of one taxonomy row |
| `GET /api/info/{dimension}`, `/api/info/{dimension}/{id}` | Taxonomy rows with counts + feature image |
| `GET /api/info/author/{letter}` | Authors by first letter |
| `GET /api/search?q=` | Full-text (phrases `"…"`, `or`, `-word`); also accepts the filter params |
| `GET /api/filter?au=&fo=&lo=&sc=&ti=&ty=` | Filter by taxonomy ids |
| `GET /api/random?limit=` | 1–50 random artworks |
| `POST /api/logger` | `{"category": "...", "value": "..."}` → 201 |
| `GET /api/logs` | Needs `X-API-Key` |
| `GET /api/health`, `/api/health/ready` | Liveness / readiness (DB) |

Lists take `page` (≥1) and `limit` (1–100, larger values are clamped).

```json
{ "success": true, "records": [ … ], "pagination": { "total": 120, "page": 1, "limit": 10, "pages": 12 } }
```

Single items return `{"success": true, "record": {…}}`. Errors are RFC 9457 `application/problem+json` with `"success": false`. An empty result is `200` with `"records": []`; a missing item is `404`.

## Operations

```bash
php bin/console app:db:migrate [--dry-run]   # apply migrations/*.sql (advisory-locked, transactional)
php bin/console app:db:feature-images        # choose a representative artwork per taxonomy row
php bin/console app:logs:prune --days=90     # delete old log rows (they contain IP addresses)
```

Schedule `app:logs:prune` (cron). Logs go to stderr as JSON in `prod`.

Behaviour worth knowing:

- Responses carry `Cache-Control` + `ETag` (lists 5 min, taxonomies 1 h, search 1 min); clients and CDNs get `304`s.
- Rate-limit state lives in `cache.app` (filesystem). With several app instances, point `cache.app` at Redis so limits are shared.
- Search terms are written to `log_table` *after* the response is sent; failures are logged and never affect the request.

## Quality

```bash
composer test    # PHPUnit: unit + functional (needs the Postgres in .env.test)
composer stan    # PHPStan level 6
composer cs      # php-cs-fixer check (composer cs:fix to apply)
```

The functional tests boot the real kernel, migrate the `DATABASE_URL` from `.env.test` (`mgoart_test`) and load `tests/Fixtures/seed.sql`. **That database is truncated on each run; never point it at real data.** CI is in `.github/workflows/ci.yml`.

## Upgrading from the previous version

**Database.** Run `php bin/console app:db:migrate`. If you have the old quoted upper-case schema (`"ART"`, `"AUTHOR_ID"`, …), migration `0001` renames everything to lower case in place, `0002` adds the generated search vectors, indexes and foreign keys, and `0003` installs `update_feature_images()`. Back up first. `log_table."timestamp"` becomes `created_at`; the stray `search_artdata` / `get_author_stats` SQL functions are gone.

```bash
sudo -u postgres psql   #connect via admin
```
```sql
CREATE USER mercurial WITH PASSWORD 'qwe';
CREATE DATABASE symfony_api OWNER mercurial;
GRANT ALL PRIVILEGES ON DATABASE symfony_api TO mercurial;
```
```bash
pg_restore -U mercurial -d symfony_api migrations/symfony_api_data.dump
php bin/console app:db:migrate
#    pg_dump -U mercurial -d symfony_api -F c -b -f symfony_api_data.dump
```

**API changes**

- `DATABASE_URL` replaces `DATABASE_DSN` / `DATABASE_USER` / `DATABASE_PASSWORD`.
- Single-item routes (`/api/art/all/{id}`, `/api/info/{dimension}/{id}`) return `record` (object) instead of a one-element `records` list, and `404` when missing.
- Empty results are `200` (was `204` with a body); errors use problem+json; raw database errors are no longer returned.
- JSON keys are lower-case; set `API_KEY_CASE=upper` for the old upper-case keys.
- `/api/logs` requires `X-API-Key`. `/api/logger` is validated and rate limited.
- `/api/info/author*` is ordered by name, not id, and reports each author's most common school. `/api/random` no longer paginates. `limit` is capped at 100.
- Search now also matches author names and orders by relevance.
