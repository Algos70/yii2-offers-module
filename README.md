Yii2 Offers Module
==================

Yii 2 **advanced** application (2.0.55) with a separate frontend and backend
application, MySQL 8.4 and PHP 8.4. Plain MVC, no frontend framework — the code
is meant to be read.

The admin panel manages casinos and their bonus offers: CRUD for both, with
search, filtering, sorting and pagination on the offer list.

Layout
------

```
common/     shared code: ActiveRecord models, enums, fixtures, config
console/    console app: migrations (console/migrations) + seed command
frontend/   public facing web app  (frontend/web is its docroot)
backend/    admin web app          (backend/web is its docroot, login required)
environments/  templates used by `php init` to generate *-local.php configs
docker/     MySQL init scripts (creates the test schema)
docs/       implementation plan and decision log
```

Requirements
------------

* PHP >= 8.2 with `pdo_mysql`, `intl`, `mbstring` (verified on PHP 8.4.22)
* Composer 2
* Docker (for MySQL) or a local MySQL 8.x

Setup
-----

```bash
composer install
php init --env=Development --overwrite=All   # generates config/*-local.php, runtime dirs
docker compose up -d                         # MySQL 8.4 on 127.0.0.1:3306
php yii migrate                              # schema for yii2_offers
php yii seed/admin admin admin@example.com admin123   # one activated admin user
php yii seed/offers                          # 6 casinos, 60 offers, 42 terms rows
```

Database defaults (see `common/config/main-local.php`, generated from
`environments/dev/common/config/main-local.php`):

| setting  | value                    |
|----------|--------------------------|
| host     | `127.0.0.1:3306`         |
| database | `yii2_offers`            |
| user     | `root` / password `root` |
| test db  | `yii2_offers_test`       |

Running
-------

```bash
# The frontend uses pretty URLs, so the built-in server needs the router
# script; without it /offers and /offer/<slug> have no file to match and 404.
php yii serve --port=8080 --docroot=@frontend/web --router=frontend/web/router.php
php yii serve --port=8081 --docroot=@backend/web    # http://localhost:8081 (admin)
```

Apache and nginx do the same thing with a rewrite rule; the router script is
only for `php yii serve` and the acceptance suite.

The backend is behind a login; use the account created by `seed/admin` above.
The public site has no accounts at all — see *Public site* below.

Schema
------

Three tables, created by the migrations in `console/migrations/`.

**`casino`** — `name`, `slug` (unique), `rating` `DECIMAL(2,1)`, `is_active`,
unix `created_at` / `updated_at`.
Indexes: unique `idx-casino-slug`, `idx-casino-is_active`.
Check: `rating BETWEEN 0 AND 5`.

**`offer`** — `casino_id` (FK, `ON DELETE CASCADE`), `title`, `slug` (unique),
`type` `ENUM('welcome','no_deposit','free_spins')`, `amount` `DECIMAL(12,2)`,
`expires_at` `DATETIME NULL` (null = never expires),
`status` `ENUM('draft','active','expired')`, unix timestamps.
Indexes: unique `idx-offer-slug`, `idx-offer-casino_id`, `idx-offer-type`, and
the composite `idx-offer-status-expires_at`, which serves the public predicate
`status = 'active' AND (expires_at IS NULL OR expires_at > NOW())`.
Check: `amount >= 0`.

**`offer_terms`** — the bonus conditions: `wagering_multiplier`, `min_deposit`,
`max_bonus`, `max_cashout`, `valid_days`, `terms_url`, `terms_note`.

`offer_id` is **both the primary key and the foreign key**
(`ON DELETE CASCADE`), so an offer has at most one terms row and the row dies
with its offer. Terms live in their own table rather than as columns on `offer`
because they are optional as a group, the listing query never reads them, and
they stay individually typed — `CHECK` constraints enforce the ranges
(`wagering_multiplier` 0–200, `valid_days` 1–365, the money columns `>= 0`) and
"wagering ≤ 35" is an indexed `WHERE` instead of a JSON extract.

Validation worth knowing about (`common/models/`):

* slugs are generated from the name/title by `SluggableBehavior` when left
  empty (`ensureUnique` appends `-2`, `-3`), and a slug typed by hand is kept;
* a **new** `expires_at` must be in the future, but an untouched past date
  stays editable — otherwise a lapsed offer could never be corrected;
* an offer whose expiry has passed cannot be set to `active`;
* a `welcome` offer that states any terms must state its `min_deposit`; a
  `no_deposit` offer must not carry one; an offer with no terms at all is fine;
* `max_cashout` may not sit below `max_bonus`.

Offer and terms are written together in one transaction by
`Offer::saveWithTerms()`; blanking out every terms field deletes the row rather
than storing an all-null one.

Admin
-----

* `/casino/index` — grid with filters on name, slug and active flag.
* `/offer/index` — grid with filters on casino, type, status, title and
  "max wagering", sortable columns (including the related casino name and
  wagering value), 20 rows per page.
* Both controllers are behind `AccessControl` (`roles => ['@']`); deletes are
  POST-only via `VerbFilter` and carry the CSRF token.

Public site
-----------

Pretty URLs are on (`frontend/config/main.php`), so the public routes are
paths. On PHP's built-in server they need the router script — see *Running*.

| URL | page |
|-----|------|
| `/` | landing page: catalogue counts, type shortcuts, latest offers |
| `/offers` | all live offers, filterable by type and casino, 20 per page |
| `/offer/<slug>` | one offer with its bonus terms |
| `/casino/<slug>` | one casino, its rating and its live offers |
| `/sitemap.xml` | generated from the database |

Every one of those pages starts from the same scope,
`Offer::find()->publiclyVisible()` — published, not expired, and belonging to
an active casino. A draft, an expired offer, one whose date has quietly lapsed
and a slug that never existed all return the **same** 404 body, because a
distinguishable one would let anyone enumerate unpublished offers.

There is no login, signup or password reset on the public site, and that is
deliberate: both applications share the `user` table and the backend authorises
on `roles => ['@']`, so any active user is an administrator. Accounts come from
`php yii seed/admin` only.

### Sitemap

`/sitemap.xml` is built by `frontend/components/Sitemap.php` from the same
visibility scope, so it can never advertise a URL that 404s. It lists the home
page, the offer listing, one entry per casino with something to show, and one
per visible offer. Draft and expired offers never appear.

`lastmod` is a W3C datetime: for an offer it is the later of `offer.updated_at`
and its terms' `updated_at` (editing the terms changes the page), and for a
casino the latest of its own row and every offer listed on it. The query is
batched, so the document costs a bounded amount of memory however large the
catalogue grows.

N+1 check
---------

The offer listing eager-loads both relations with
`Offer::find()->joinWith(['casino', 'terms'])`, so its query count is constant
in the number of rows rendered.

**How this was verified.**

1. *Measured directly.* With the schema cache warm, counting executed
   statements from the framework logger while running `OfferSearch::search()`:

   ```
   pageSize=20   queries=4
   pageSize=100  queries=4
   ```

   The four are `SELECT COUNT(*)` over the joined query, the offer page itself,
   `SELECT * FROM casino WHERE id IN (...)` and
   `SELECT * FROM offer_terms WHERE offer_id IN (...)`.
   Note when reproducing this: every query is logged three times (one
   `LEVEL_INFO` record plus a profiling begin/end pair), so filter on the level
   or the count comes out tripled; and a cold run adds one-off schema
   introspection per table.

2. *Pinned by a test*, `backend/tests/Unit/Models/OfferSearchTest.php::testListingDoesNotScaleQueriesWithRowCount`:
   it asserts `isRelationPopulated('casino')` and `isRelationPopulated('terms')`
   for every row, then re-counts after touching all of them. Remove the eager
   loading and the relations turn lazy, so the count grows by one per row and
   the test fails.

3. *Visible in the browser.* With the 30 seeded offers, the Yii Debug toolbar's
   DB panel reports the same count (18 for the whole request, including
   session, identity and debug queries) on page 1 with 20 rows and on page 2
   with 10 rows.

Tests
-----

Codeception. The acceptance suite needs a web server, so use the
`php-builtin` environment, which starts one itself:

```bash
php yii_test migrate --interactive=0   # prepare yii2_offers_test
php vendor/bin/codecept build
php vendor/bin/codecept run --env php-builtin
```

Static analysis and code style:

```bash
php vendor/bin/phpstan analyse
php vendor/bin/phpcs --standard=phpcs.xml.dist   # paths come from the ruleset
```
