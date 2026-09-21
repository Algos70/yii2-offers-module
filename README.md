Yii2 Offers Module
==================

Yii 2 advanced application (2.0.55), PHP 8.4, MySQL 8.4. Two apps sharing one
database: a public site (`frontend/`) and an admin panel (`backend/`).

Requirements
------------

PHP >= 8.2 with `pdo_mysql`, `intl` and `mbstring`, Composer 2, and Docker for
MySQL (or a local MySQL 8.x on `127.0.0.1:3306`).

Setup
-----

```bash
composer install
php init --env=Development --overwrite=All            # generates config/*-local.php
docker compose up -d                                  # MySQL 8.4, database yii2_offers
php yii migrate                                       # casino, offer, offer_terms
php yii seed/admin admin admin@example.com admin123   # admin account
php yii seed/offers                                   # 6 casinos, 60 offers
```

DB defaults are in `common/config/main-local.php`: `yii2_offers`, user `root`,
password `root`; tests use `yii2_offers_test`.

Running
-------

```bash
# --router is required: the frontend uses pretty URLs, and without it the
# built-in server has no file to match for /offers. Apache and nginx use a
# rewrite rule instead.
php yii serve --port=8080 --docroot=@frontend/web --router=frontend/web/router.php
php yii serve --port=8081 --docroot=@backend/web
```

| | URL | |
|---|---|---|
| Public | http://localhost:8080 | `/`, `/offers`, `/offer/<slug>`, `/casinos`, `/casino/<slug>`, `/sitemap.xml` |
| Admin | http://localhost:8081 | casino and offer CRUD |

Admin login
-----------

| username | password |
|----------|----------|
| `admin`  | `admin123` |

Created by `php yii seed/admin <username> <email> <password>` above. The public
site has **no** login or signup on purpose: both apps share the `user` table and
the backend authorises on `roles => ['@']`, so any active user is an
administrator — a public signup form would hand out the admin panel.

N+1 check
---------

Both offer listings eager-load their relations with
`Offer::find()->joinWith(['casino', 'terms'])`, so the query count does not grow
with the number of rows rendered. Verified three ways:

1. **Counted the queries.** Running `OfferSearch::search()` while counting
   `yii\db\Command` log records gives **4 queries at `pageSize` 20 and 4 at
   `pageSize` 100**: `COUNT(*)`, the offer page, `casino WHERE id IN (...)` and
   `offer_terms WHERE offer_id IN (...)`. Two traps when reproducing this: every
   query is logged three times (one `LEVEL_INFO` record plus a profiling pair),
   and a cold run adds one-off schema introspection per table — measure with the
   schema cache warm.
2. **Pinned it in a test.**
   `backend/tests/Unit/Models/OfferSearchTest.php::testListingDoesNotScaleQueriesWithRowCount`
   asserts `isRelationPopulated()` for both relations on every row, then
   re-counts after touching them all. Drop the eager loading and the relations
   turn lazy, the count grows per row, and the test fails.
3. **Read the debug toolbar.** With the seeded data, `/offer/index` reports the
   same DB count on page 1 (20 rows) and page 2 (10 rows).

The casino listing does the same for its per-casino offer counts: one grouped
`COUNT(*) … GROUP BY casino_id`, not one count per card.

Tests
-----

```bash
php yii_test migrate --interactive=0
php vendor/bin/codecept build
php vendor/bin/codecept run --env php-builtin   # 184 tests
php vendor/bin/phpstan analyse
php vendor/bin/phpcs --standard=phpcs.xml.dist
```

The acceptance suite needs a web server, which the `php-builtin` environment
starts itself.

Note on `terms`
---------------

The brief lists `terms` as one field on `offer`. It is a separate table here,
`offer_terms`, with `offer_id` as **both** primary key and foreign key — so an
offer has at most one terms row and it dies with its offer.

The reason is that bonus terms are not prose but a small fixed vocabulary:
wagering, minimum deposit, maximum bonus, maximum cashout, validity window.
As typed columns they get `CHECK` constraints and real validation (a welcome
offer must state a minimum deposit, a no-deposit one must not), the admin can
filter and sort on "wagering ≤ 35" as an indexed `WHERE`, and the public card
renders each value with its own icon instead of dumping a paragraph. A single
text column would have made all of that string handling.

They live in their own table rather than as columns on `offer` because they are
optional as a group — `saveWithTerms()` writes no row when the admin leaves the
fields blank and deletes it when they are cleared — and because the listing
query never reads them, so `offer` stays narrow.

More detail — schema, validation rules and the decisions behind them — is in
`docs/offers-admin-implementation-plan.md`.
