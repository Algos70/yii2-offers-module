Offers Module — Admin (Backend) Implementation Plan
===================================================

**Goal:** the admin half of the Offers module — schema, shared domain models,
validated CRUD for `casino` and `offer`, a filterable/sortable/paginated offer
list, and seed data — on the existing Yii2 advanced scaffold.

**Scope:** `console/migrations/`, `common/` (enums, models, fixtures),
`backend/` (controllers, search models, views), `console/controllers/` (seed).

**Out of scope (not planned, not touched here):** the public `/offers` and
`/offer/<slug>` pages and `/sitemap.xml` — they live in the `frontend/`
application and get their own plan. The domain models and query scopes built in
Epic 1 are the seam they will reuse, so nothing in this plan hardcodes admin-only
assumptions into `common/`.

Stack facts (verified in this repo)
-----------------------------------

| item | value |
|------|-------|
| framework | `yiisoft/yii2` 2.0.55, advanced template |
| PHP | 8.4.22 (`composer.json` floor: `>=8.2`) |
| DB | MySQL 8.4 — `yii2_offers`, tests on `yii2_offers_test`; `CHECK` constraints are enforced (verified: violating insert fails with `ERROR 3819`) |
| UI | `yiisoft/yii2-bootstrap5` 2.0.51 (`ActiveForm`, `Nav`, `LinkPager`), core `yii\grid\GridView` |
| tests | Codeception 5 — `backend/tests/{Unit,Functional}`, `common/tests/Unit` |
| dev tools | Gii and Debug modules enabled in `backend/config/main-local.php` under `YII_ENV_DEV` |

Data model decision: terms are a child table
--------------------------------------------

The brief lists `terms` as one field on `offer`. A free-text blob is the wrong
surface: bonus terms are a small, fixed, numeric vocabulary (wagering, minimum
deposit, caps, validity window) that the admin filters and sorts on and the
public detail page renders field by field. They are modelled as a **strict 1:1
child table** `offer_terms`:

- `offer_terms.offer_id` is **both primary key and foreign key**
  (`ON DELETE CASCADE`), so exactly zero or one terms row can exist per offer —
  no duplicates are representable, no surrogate id, no ordering column.
- `offer` stays narrow and is the only table the listing query reads; terms are
  joined or eager-loaded only where they are shown or filtered.
- Terms are optional as a group (an offer can exist before its terms are
  entered) instead of seven nullable columns on the main table.
- Numeric fields are real typed columns, so "wagering ≤ 35" is an indexed
  `WHERE`, sorting works through `ActiveDataProvider`, and ranges are enforced by
  `CHECK` constraints in addition to model rules.

Rejected alternatives, recorded so the choice is reviewable:

- **MySQL `JSON` column.** MySQL has `JSON`, not Postgres `jsonb`. The database
  would validate nothing beyond well-formedness, every filter would become
  `JSON_EXTRACT(...)` with no index unless a generated column is added per key
  (which is just columns with extra steps), and sorting via `Sort::$attributes`
  gets awkward. JSON fits write-rarely / read-whole / never-filtered payloads;
  terms are the opposite.
- **EAV child table** (`offer_term(offer_id, label, value, position)`). `value`
  as a string kills range queries and typed validation, and a list view invites
  one query per offer. It is the right shape only for arbitrary free-form bullet
  lists; if that is wanted later it is purely additive next to the typed table.

Global constraints
------------------

1. **Shared models live in `common/models/`.** `backend` and the future
   `frontend` both consume them; no duplicated AR classes.
2. **Every schema change is a migration** in `console/migrations/`, applied with
   `php yii migrate` and `php yii_test migrate`. Both `safeUp()` and
   `safeDown()` must work — `migrate/down 3` has to leave a clean database.
3. **Timestamps follow the template:** `created_at` / `updated_at` are unix ints
   fed by `TimestampBehavior`, exactly like `{{%user}}`. `expires_at` is a
   user-entered `DATETIME` instead, because it is displayed, validated against
   "now", and later becomes sitemap `lastmod` input.
4. **Enum columns are MySQL `ENUM`**, mirrored by PHP 8 backed enums in
   `common/enums/`. The DB enforces the domain, the enum class is the single
   source of truth for labels and for the `in` validator range. MySQL-only is
   acceptable: the app and both test databases are MySQL 8.4.
5. **No raw SQL string interpolation.** Filters go through `andFilterWhere()` /
   parameter binding; sorting through an explicit `Sort::$attributes` whitelist.
6. **Escaping is explicit.** `Html::encode()` for any echoed attribute,
   `GridView` columns keep default encoding, `raw` format is never used on user
   data. CSRF stays enabled (`_csrf-backend`); every mutating action is
   POST-only via `VerbFilter`.
7. **No unbounded queries.** Every list endpoint is paginated; every offer list
   eager-loads the relations it renders.
8. **Writes that span `offer` + `offer_terms` run in one transaction.**
9. **Commits are Conventional Commits in English**, one per story, and are only
   created when the repository owner asks for them.

Definition of done for every story: code written, the named verification command
run and passing, and the story's acceptance criteria observable in the running
app (`php yii serve --port=8081 --docroot=@backend/web`).

---

Epic 1 — Schema and shared domain
---------------------------------

Produces the tables, the enum vocabulary, and the ActiveRecord classes with
validation that actually holds. Nothing in this epic depends on `backend/`.

### Story 1.1 — `casino` table migration

**Files:** create `console/migrations/mYYMMDD_HHMMSS_create_casino_table.php`

| column | type | notes |
|--------|------|-------|
| `id` | `primaryKey()` | |
| `name` | `string(120)->notNull()` | |
| `slug` | `string(140)->notNull()` | unique index `idx-casino-slug` |
| `rating` | `decimal(2,1)->notNull()->defaultValue(0)` | 0.0–5.0 |
| `is_active` | `boolean()->notNull()->defaultValue(true)` | `TINYINT(1)` |
| `created_at` | `integer()->notNull()` | |
| `updated_at` | `integer()->notNull()` | |

Table options when `$this->db->driverName === 'mysql'`:
`CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB` (same guard
style as the `init` migration, utf8mb4 to match the app's charset config).
Index `idx-casino-is_active` on `is_active` (the public listing filters on it).
`CHECK (rating >= 0 AND rating <= 5)`.

**Acceptance:** `php yii migrate --interactive=0` then
`php yii migrate/down 1 --interactive=0` both succeed; `SHOW CREATE TABLE casino`
shows the unique slug index, the check constraint and utf8mb4; inserting
`rating = 9` fails at the DB level.

**Commit:** `feat(db): add casino table migration`

### Story 1.2 — `offer` table migration

**Files:** create `console/migrations/mYYMMDD_HHMMSS_create_offer_table.php`

| column | type | notes |
|--------|------|-------|
| `id` | `primaryKey()` | |
| `casino_id` | `integer()->notNull()` | FK |
| `title` | `string(160)->notNull()` | |
| `slug` | `string(180)->notNull()` | unique index `idx-offer-slug` |
| `type` | `"ENUM('welcome','no_deposit','free_spins') NOT NULL"` | raw type string |
| `amount` | `decimal(12,2)->notNull()` | currency for deposit offers, spin count for `free_spins` |
| `expires_at` | `dateTime()` | nullable = never expires |
| `status` | `"ENUM('draft','active','expired') NOT NULL DEFAULT 'draft'"` | raw type string |
| `created_at` / `updated_at` | `integer()->notNull()` | |

Foreign key `fk-offer-casino_id` → `casino(id)`, `ON DELETE CASCADE`,
`ON UPDATE CASCADE` — an offer cannot outlive its casino.
`CHECK (amount >= 0)`.

Indexes, chosen for the real access paths:
- `idx-offer-casino_id` on `casino_id` — admin filter and relation lookups.
- `idx-offer-type` on `type` — admin and public filters.
- `idx-offer-status-expires_at` on `(status, expires_at)` — the public predicate
  `status = 'active' AND (expires_at IS NULL OR expires_at > NOW())` and the
  sitemap query.

`safeDown()` drops the FK before the table.

**Acceptance:** migrate up/down clean; `EXPLAIN` on the public predicate reports
`idx-offer-status-expires_at` in `possible_keys`; inserting an unknown `type`
is rejected by MySQL; deleting a casino removes its offers.

**Commit:** `feat(db): add offer table migration`

### Story 1.3 — `offer_terms` table migration

**Files:** create `console/migrations/mYYMMDD_HHMMSS_create_offer_terms_table.php`

| column | type | notes |
|--------|------|-------|
| `offer_id` | `integer()->notNull()` | **PRIMARY KEY and FK** — enforces 1:1 |
| `wagering_multiplier` | `decimal(5,1)` | `35.0` renders as "35x"; NULL = none |
| `min_deposit` | `decimal(10,2)` | NULL for `no_deposit` / `free_spins` |
| `max_bonus` | `decimal(10,2)` | cap on the granted bonus |
| `max_cashout` | `decimal(10,2)` | cap on withdrawable winnings |
| `valid_days` | `smallInteger()` | days to use the offer after claiming |
| `terms_url` | `string(255)` | link to the casino's full legal T&C |
| `terms_note` | `string(500)` | short residual caveat, plain text |
| `created_at` / `updated_at` | `integer()->notNull()` | |

```php
$this->addPrimaryKey('pk-offer_terms-offer_id', '{{%offer_terms}}', 'offer_id');
$this->addForeignKey(
    'fk-offer_terms-offer_id',
    '{{%offer_terms}}',
    'offer_id',
    '{{%offer}}',
    'id',
    'CASCADE',   // delete
    'CASCADE',   // update
);
```

`CHECK` constraints (MySQL 8.4 enforces them — verified):

```sql
CHECK (wagering_multiplier IS NULL OR wagering_multiplier BETWEEN 0 AND 200)
CHECK (min_deposit  IS NULL OR min_deposit  >= 0)
CHECK (max_bonus    IS NULL OR max_bonus    >= 0)
CHECK (max_cashout  IS NULL OR max_cashout  >= 0)
CHECK (valid_days   IS NULL OR valid_days BETWEEN 1 AND 365)
```

Index `idx-offer_terms-wagering_multiplier` on `wagering_multiplier` — the admin
list gets a "max wagering" filter (Story 2.4).

**Acceptance:** migrate up/down clean; inserting two rows with the same
`offer_id` fails on the primary key; `wagering_multiplier = 500` fails the check;
deleting an offer removes its terms row; `SHOW CREATE TABLE offer_terms` shows
`PRIMARY KEY (offer_id)` and the FK.

**Commit:** `feat(db): add offer terms table migration`

### Story 1.4 — enum vocabulary

**Files:** create `common/enums/OfferType.php`, `common/enums/OfferStatus.php`

```php
enum OfferType: string
{
    case Welcome = 'welcome';
    case NoDeposit = 'no_deposit';
    case FreeSpins = 'free_spins';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string,string> value => human label, for dropdowns */
    public static function labels(): array
    {
        return [
            self::Welcome->value => 'Welcome bonus',
            self::NoDeposit->value => 'No deposit',
            self::FreeSpins->value => 'Free spins',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value];
    }
}
```

`OfferStatus` has the same shape for `draft` / `active` / `expired`.
`values()` feeds every `in` validator; `labels()` feeds every `ActiveForm`
dropdown and `GridView` filter, so admin copy has one source.

Both enums also expose `labelFor(?string $value): string`, because AR
attributes are raw strings in views and `OfferType::from($offer->type)` would
throw on unexpected data.

**Acceptance (as built):** `common/tests/Unit/Enums/OfferTypeTest.php` and
`OfferStatusTest.php` assert the exact `values()` order, that `labels()` has a
key for every case and no extras, that `labelFor()` returns `''` for unknown
and `null` input — plus a **drift guard** that reads the live column
definition, since the PHP enum and the MySQL `ENUM` are two declarations of one
domain:

```php
$column = Yii::$app->db->getTableSchema('{{%offer}}', true)?->getColumn('type');
self::assertSame(OfferType::values(), $column->enumValues);
```

Adding a case without a migration (or vice versa) then fails in CI instead of
at insert time.
Run: `php vendor/bin/codecept run common/tests/Unit`.

**Commit:** `feat(domain): add offer type and status enums`

### Story 1.5 — `Casino` model

**Files:** create `common/models/Casino.php`

- `tableName(): '{{%casino}}'`.
- `behaviors()` — `TimestampBehavior::class` plus the framework's own slug
  generator, which already has the semantics the brief asks for:

```php
[
    'class' => SluggableBehavior::class,   // yii\behaviors\SluggableBehavior
    'attribute' => 'name',                 // 'title' on Offer
    'slugAttribute' => 'slug',
    'ensureUnique' => true,                // appends -2, -3, ... on collision
    'immutable' => true,                   // a slug the admin typed is kept as-is
]
```

  `SluggableBehavior::isNewSlugNeeded()` regenerates only while `slug` is empty
  (`immutable => true` short-circuits otherwise), so "auto-generate from the
  name/title when left empty" needs no custom code, and `ensureUnique` keeps the
  unique index intact. The behavior runs on `beforeValidate`, so the `unique` and
  `match` rules below still see the generated value.
- Rules:
  - `name` — `trim`, `required`, `string` max 120.
  - `slug` — `trim`, `string` max 140, `match` `/^[a-z0-9]+(?:-[a-z0-9]+)*$/`,
    `unique`; **not** `required` (the behavior fills it).
  - `rating` — `required`, `number` with `min => 0`, `max => 5`.
  - `is_active` — `boolean`, `default` value `true`.
- `getOffers(): ActiveQuery` — `hasMany(Offer::class, ['casino_id' => 'id'])`.
  **Deferred to Story 1.6** during implementation: `Offer` does not exist yet at
  this point and the repo's PHPStan run (level 5) fails the forward reference
  with three `class.notFound` errors. The relation and its `@property-read`
  docblock are added when `Offer` lands.
- `attributeLabels()` — `is_active` as "Active", `rating` as "Rating (0-5)".

**Acceptance:** `common/tests/Unit/Models/CasinoTest.php` covers: blank slug is
derived from the name; a second casino with the same name gets `-2`;
`rating = 6` fails; an explicitly typed `slug = 'Not A Slug'` fails the pattern
and is not rewritten by the behavior.
Run: `php vendor/bin/codecept run common/tests/Unit`.

**Commit:** `feat(domain): add casino model with slug generation`

### Story 1.6 — `Offer` model and query scopes

**Files:** create `common/models/Offer.php`, `common/models/OfferQuery.php`

- `behaviors()` — `TimestampBehavior::class` plus `SluggableBehavior` configured
  as in Story 1.5 but with `'attribute' => 'title'`, same `ensureUnique` and
  `immutable` pair.
- Rules:
  - `casino_id` — `required`, `integer`, `exist` with
    `targetClass => Casino::class, targetAttribute => 'id'`.
  - `title` — `trim`, `required`, `string` max 160.
  - `slug` — `trim`, `string` max 180, `match` `/^[a-z0-9]+(?:-[a-z0-9]+)*$/`,
    `unique`; not `required` — the behavior derives it from `title`.
  - `type` — `required`, `in` with `range => OfferType::values()`.
  - `status` — `required`, `in` with `range => OfferStatus::values()`,
    `default` `OfferStatus::Draft->value`.
  - `amount` — `required`, `number`, `min => 0`.
  - `expires_at` — `datetime` with `format => 'php:Y-m-d H:i:s'`, plus inline
    validator `validateExpiresAtInFuture()` rejecting a value `<= now()` **only
    when the attribute changed** (`$this->isAttributeChanged('expires_at')`), so
    an old record whose date has passed can still be edited and moved to
    `expired`. Null is allowed.
  - Consistency: `status = 'active'` together with an already-past `expires_at`
    is rejected with the error attached to `status` ("an expired offer cannot be
    active"). Implemented as a **second, separate** inline validator
    (`validateStatusAgainstExpiry()`), not as part of the expiry rule: it must
    fire even when `expires_at` was not touched in this request, while
    `validateExpiresAtInFuture()` must not.
- `isExpired(): bool` — helper used by views and by the status rule's tests.
- Relations:
  - `getCasino(): ActiveQuery` — `hasOne(Casino::class, ['id' => 'casino_id'])`.
  - `getTerms(): ActiveQuery` — `hasOne(OfferTerms::class, ['offer_id' => 'id'])`.
    **Deferred to Story 1.7** for the same reason as `Casino::getOffers()`:
    `OfferTerms` does not exist yet and PHPStan rejects the forward reference.
- `public static function find(): OfferQuery` with:
  - `active()` — `andWhere(['offer.status' => OfferStatus::Active->value])`
  - `notExpired()` — `andWhere(['or', ['offer.expires_at' => null], ['>', 'offer.expires_at', date('Y-m-d H:i:s')]])`.
    The boundary is a **PHP-bound timestamp, not MySQL `NOW()`**: validation
    compares against PHP's clock, MySQL's `@@session.time_zone` defaults to
    `SYSTEM`, and two clocks would let a row be "expired" for the validator and
    "live" for the query. Verified that the bound literal still uses
    `idx-offer-status-expires_at` (`EXPLAIN` reports the index).
  - `withCasino()` — `with(['casino'])`
  - `withTerms()` — `with(['terms'])`. **Deferred to Story 1.7** with the relation.
  These are the listing and sitemap primitives; the admin search model uses
  `withCasino()`, the detail views add `withTerms()`.
- `saveWithTerms(OfferTerms $terms): bool` — the aggregate write, so controllers
  stay thin and the transaction rule cannot be forgotten. **Deferred to Story
  1.7**, since its parameter type is `OfferTerms`:

```php
public function saveWithTerms(OfferTerms $terms): bool
{
    $transaction = static::getDb()->beginTransaction();
    try {
        if (!$this->save(false)) {            // already validated by the caller
            $transaction->rollBack();
            return false;
        }
        $terms->offer_id = $this->id;
        if (!$terms->save(false)) {
            $transaction->rollBack();
            return false;
        }
        $transaction->commit();
        return true;
    } catch (\Throwable $e) {
        $transaction->rollBack();
        throw $e;
    }
}
```

**Acceptance (as built, 16 cases):** `common/tests/Unit/Models/OfferTest.php`
covers: unknown `type` and unknown `status` fail; `casino_id` pointing at a
missing casino fails; a malformed `expires_at` (`31/12/2030`) fails; a new
`expires_at` in the past fails while a future one and `null` pass; a **stored**
past expiry can still be edited (title change validates); publishing that same
lapsed row fails on `status`; `status` defaults to `draft`; blank slug derived
from title and made unique (`visible-welcome-bonus-2`); negative `amount`
fails; `Offer::find()->active()->notExpired()` returns exactly `[1, 2]` of the
five fixture rows; `withCasino()` populates the relation on every row;
`Casino::findOne(2)->offers` returns that casino's offers; `isExpired()`
matches the stored date.
Fixtures: `common/fixtures/{CasinoFixture,OfferFixture}.php` plus
`common/tests/Support/data/{casino,offer}.php`. `OfferFixture::$depends`
names `CasinoFixture` because the FK forbids the other order. One fixture row
(`activeButLapsed`) is deliberately in a state the model rules forbid —
`status = 'active'` with a past expiry — because that is exactly the stale row
`notExpired()` must hide, and only direct table writes can produce it.
`saveWithTerms()` coverage moves to Story 1.7 with the method itself.
Run: `php yii_test migrate --interactive=0 && php vendor/bin/codecept run common/tests/Unit`.

**Commit:** `feat(domain): add offer model with validation and query scopes`

### Story 1.7 — `OfferTerms` model

**Files:** create `common/models/OfferTerms.php`,
`common/fixtures/OfferTermsFixture.php`,
`common/tests/Support/data/offer_terms.php`; modify `common/models/Offer.php`
and `common/models/OfferQuery.php`

**Also lands here** (deferred out of Story 1.6, where the class did not exist
yet and PHPStan rejected the forward references):
- `Offer::getTerms(): ActiveQuery` plus its `@property-read` docblock entry;
- `OfferQuery::withTerms()`;
- `Offer::saveWithTerms(OfferTerms $terms): bool` and its rollback test.

- `tableName(): '{{%offer_terms}}'`, `behaviors(): [TimestampBehavior::class]`,
  primary key is `offer_id` (Yii reads it from the schema; no override needed).
- `public ?string $offerType = null;` — **validation context**, not a column.
  The type-dependent rules live here, but the discriminator lives on the parent,
  so the caller sets `$terms->offerType = $offer->type` before validating. The
  relation is not used for this because on create the parent has no id yet.
- Rules:
  - `wagering_multiplier` — `number`, `min => 0`, `max => 200`.
  - `min_deposit`, `max_bonus`, `max_cashout` — `number`, `min => 0`.
  - `valid_days` — `integer`, `min => 1`, `max => 365`.
  - `terms_url` — `url` with `defaultScheme => 'https'`, `string` max 255.
  - `terms_note` — `trim`, `string` max 500.
  - `min_deposit` — `required` `when` `fn (self $m) => $m->offerType === OfferType::Welcome->value`
    (a welcome bonus without a minimum deposit is meaningless).
  - `min_deposit` — must be empty for a no-deposit offer: inline validator
    `validateNotApplicable()` `when`
    `fn (self $m) => $m->offerType === OfferType::NoDeposit->value`.
  - `max_cashout` — `compare` with `compareAttribute => 'max_bonus'`,
    `operator => '>='`, `skipOnEmpty => true` (a cap below the bonus is a typo).
  - `['!offer_id', 'safe']` is **not** declared — `offer_id` is never mass
    assigned; `saveWithTerms()` sets it.
- `getOffer(): ActiveQuery` — `hasOne(Offer::class, ['id' => 'offer_id'])`.
- `isEmpty(): bool` — true when every user-facing attribute is null/`''`.
  Drives the lifecycle of the optional row inside `saveWithTerms()`: an empty
  terms object is **not written**, and blanking out an existing one **deletes**
  the row, so `offer_terms` never accumulates all-null noise. After a
  successful write the relation is repopulated on the offer, so callers do not
  see a stale value.
- `attributeLabels()` — "Wagering (x)", "Min deposit", "Max bonus",
  "Max cashout", "Valid for (days)", "Full T&C URL", "Extra note".

**Acceptance (as built, 14 cases):** `common/tests/Unit/Models/OfferTermsTest.php`
covers: `wagering_multiplier` 500 and −1 fail while 200 passes; `valid_days` 0
and 366 fail; `terms_url = 'javascript:alert(1)'` fails the `url` validator
while `example.com/terms` is accepted and rewritten to
`https://example.com/terms` by `defaultScheme`; `max_cashout = 50` under
`max_bonus = 100` fails; `offerType = 'welcome'` without `min_deposit` fails;
`offerType = 'no_deposit'` with a `min_deposit` fails; `offerType = 'free_spins'`
validates with all-null numerics; `isEmpty()` ignores the foreign key and the
timestamps; both relation directions resolve and offer 3 has `terms === null`;
`withTerms()` populates the relation on every row; `saveWithTerms()` writes both
rows, skips an empty terms object, deletes the row when it is blanked out, and
**rolls the offer back** when the terms insert trips
`chk-offer_terms-valid_days` (asserted by offer count and a slug lookup).
Fixtures: `common/fixtures/OfferTermsFixture.php` (`$depends` on
`OfferFixture`) plus `common/tests/Support/data/offer_terms.php`, which gives
terms to offers 1 and 2 only — offers 3–5 exercise the optional side.
Run: `php vendor/bin/codecept run common/tests/Unit`.

**Commit:** `feat(domain): add offer terms model with conditional validation`

---

Epic 2 — Admin CRUD
-------------------

All of it behind the existing login (`common\models\LoginForm`, `AccessControl`
with `roles => ['@']`). Views use `yii\bootstrap5\ActiveForm` and core
`GridView`; layout is the template's `backend/views/layouts/main.php`.

### Story 2.1 — admin shell: access gate and navigation

**Files:** modify `backend/views/layouts/_header.php`; create
`backend/tests/Functional/NavigationCest.php`

Add `Casinos` (`['/casino/index']`) and `Offers` (`['/offer/index']`) nav items,
both `'visible' => !Yii::$app->user->isGuest`, before the Login/Logout items.

**Acceptance (as built):** `NavigationCest` asserts a guest sees neither link
and a signed-in user sees both. The planned "guest hitting `/offer/index` is
redirected to `site/login` (302)" check **moves to Stories 2.2 and 2.3**: the
redirect comes from each controller's `AccessControl` filter, and until those
controllers exist the route 404s instead — a nav entry cannot gate anything by
itself.
Run: `php vendor/bin/codecept run backend/tests`.

**Commit:** `feat(backend): add casino and offer navigation entries`

### Story 2.2 — casino CRUD

**Files:** create `backend/controllers/CasinoController.php`,
`backend/models/CasinoSearch.php`,
`backend/views/casino/{index,create,update,view,_form}.php`

- Controller `behaviors()`: `AccessControl` (`roles => ['@']` for every action)
  plus `VerbFilter` (`'delete' => ['post']`) — mirrors `SiteController`'s style.
- Actions: `index` (search model → `GridView`), `view`, `create`, `update`,
  `delete` (POST, flash, redirect to `index`), private `findModel(int $id)`
  throwing `NotFoundHttpException`.
- `CasinoSearch extends Casino`: `rules()` narrows to filter attributes
  (`[['id'], 'integer']`, `[['name', 'slug'], 'safe']`, `[['is_active'], 'boolean']`),
  `scenarios()` returns `Model::scenarios()` so the parent's validation does not
  leak into filtering, and `search(array $params): ActiveDataProvider` with
  `pagination => ['pageSize' => 20]`, `sort` whitelisting
  `['id', 'name', 'slug', 'rating', 'is_active', 'created_at']`, filters via
  `andFilterWhere(['like', 'name', $this->name])` and exact matches elsewhere.
- Delete confirmation states the offer count (`$model->getOffers()->count()` —
  one aggregate query, no hydration) because the FK cascades.
- `actionCreate()` instantiates `new Casino(['is_active' => true])`. Found by
  the browser smoke test: the model's `default` rule only fires during
  validation, so an untouched create form rendered the checkbox unchecked and
  `ActiveForm`'s hidden input posted `0` — a new casino silently arrived
  inactive. Pinned by an `is_active => 1` assertion in the create test.

**Acceptance (as built):** `backend/tests/Functional/CasinoCrudCest.php`, 9
cases: a guest hitting `index|create|view|update` lands on `site/login` (the
gate check inherited from Story 2.1); the grid lists both fixture casinos;
`CasinoSearch[name]=Two` narrows it; create with a blank slug derives
`neon-nights`, flashes and stores `is_active = 1`; `rating = 9` redisplays the
form with "must be no greater than 5" and stores nothing; update persists;
`delete` over GET returns **405**; `delete` over POST **without** a CSRF token
returns **400** and keeps the row; `delete` over POST **with** the token from
the page's `csrf-token` meta tag removes it.
Run: `php vendor/bin/codecept run backend/tests`.

**Browser smoke (real UI, backend on :8081):** login → `/casino/index` lists
the three dev casinos; create form stores `browser-smoke-casino` with the slug
derived; `rating = 9` shows the inline error; `sort=-rating` reorders;
`CasinoSearch[name]=Neon` filters to one row; the create checkbox is
pre-checked and the stored record reads "Active: Yes".
Not covered in the browser: the delete link's `data-method="post"` +
`data-confirm` round trip — the relay-driven tab cannot resolve the native
`confirm()` dialog. That path is covered by the three delete tests above.

**Commit:** `feat(backend): add casino CRUD`

### Story 2.3 — offer CRUD with terms

**Files:** create `backend/controllers/OfferController.php`,
`backend/views/offer/{index,create,update,view,_form}.php`

- `behaviors()` as in Story 2.2.
- The form edits **two models in one request**. The plan originally called for
  `Model::loadMultiple()`; that is **wrong** and was corrected during
  implementation. Its source (`yii\base\Model:914-938`) keys on
  `$data[$formName][$i]`, i.e. it is for *tabular* input — many rows of the
  **same** model (`Offer[0]`, `Offer[1]`). Two *different* models each get their
  own `load()`, which already scopes by form name. `validateMultiple()` is fine
  as planned: it does accept heterogeneous models.

```php
private function saveFromRequest(Offer $offer, OfferTerms $terms): bool
{
    $post = Yii::$app->request->post();

    if (!$offer->load($post)) {       // false on a plain GET
        return false;
    }

    $terms->load($post);
    $terms->offerType = $offer->type; // validation context for the conditional rules

    if (!Model::validateMultiple([$offer, $terms])) {
        return false;
    }

    return $offer->saveWithTerms($terms);
}
```

  Both `actionCreate()` and `actionUpdate()` call it; update passes
  `$offer->terms ?? new OfferTerms()`. Symptom of the original bug, for the
  record: the form silently re-rendered **empty** after submit, because
  `loadMultiple()` returned false and neither model was populated.
- `actionIndex()` builds a plain `ActiveDataProvider` over
  `Offer::find()->withCasino()->withTerms()` with `pageSize` 20 and a sort
  whitelist; Story 2.4 swaps in `OfferSearch` with the filters. This keeps 2.3
  shippable on its own instead of rendering a view with no data.
- `findModel()` eager-loads both relations, so the detail page and the update
  form never lazy-load.
- `_form.php`: `casino_id` dropdown from
  `Casino::find()->select(['name', 'id'])->orderBy('name')->indexBy('id')->column()`
  (one query, no hydration); `type` / `status` dropdowns from the enum label
  maps; `expires_at` a text input with `YYYY-MM-DD HH:MM:SS` placeholder and a
  hint that empty means "never expires"; `slug` with the hint "leave empty to
  generate from the title"; then a `<fieldset>` "Terms" with the seven
  `OfferTerms` fields (`wagering_multiplier`, `min_deposit`, `max_bonus`,
  `max_cashout`, `valid_days` as `numberInput()`, `terms_url` as `url` input,
  `terms_note` as a 3-row textarea with `maxlength => 500`).
- `view.php` renders the offer with `DetailView` and the terms as a second
  `DetailView` over the related model, formatted through
  `Yii::$app->formatter` (`decimal`, `integer`, `url`, `datetime`);
  `terms_note` prints as `nl2br(Html::encode($terms->terms_note))` — encode
  first, then `nl2br`, since the other order would escape the `<br>` tags it
  just produced. That single column is the only `format => 'raw'` in the app,
  and its value is encoded by hand before it gets there. When
  `$offer->terms === null` the panel shows "No terms recorded for this offer."
- `index.php` grid columns: `id`, `title`, casino name via
  `['attribute' => 'casino_id', 'value' => 'casino.name']` (eager-loaded in
  Story 2.4), `type` and `status` through the enum labels, `amount`,
  `terms.wagering_multiplier` rendered as `35x` (eager-loaded), `expires_at`,
  `ActionColumn`.

**Acceptance (as built, 10 cases):** `backend/tests/Functional/OfferCrudCest.php`
— a guest hitting `index|create|view|update` lands on `site/login`; the grid
shows the casino name, the enum **labels** (not raw values) and `35x` from the
terms relation; create with a blank slug and terms writes both rows and
defaults to `draft`; create with every terms field empty writes **no**
`offer_terms` row and the detail page says "No terms recorded"; a `welcome`
offer without `min_deposit` shows "Min deposit cannot be blank." and writes
neither table; a past `expires_at` shows "Expiry date must be in the future.";
update reuses the same terms row (`COUNT(*) = 1` afterwards);
`<script>alert(1)</script>` stored in `terms_note` is *seen* as text and
`dontSeeInSource` confirms it never reaches the markup; `delete` over GET
returns 405; `delete` over POST with the CSRF token removes the offer **and**
its terms row (FK cascade).
Run: `php vendor/bin/codecept run backend/tests`.

**Browser smoke (real UI, backend on :8081):** created "Weekend Reload 50%"
with full terms — detail page shows `Slug weekend-reload-50`, `Type Welcome
bonus`, `Full T&C URL https://example.com/terms` (the `url` validator's
`defaultScheme` rewrote the typed `example.com/terms`), and the two-line note
rendered with its line break; a `no_deposit` offer carrying a `min_deposit`
was rejected inline with "Min deposit does not apply to a no-deposit offer.";
the listing row reads `Weekend Reload 50% | Golden Reels | Welcome bonus |
Active | 50.00 | 35x`.

**Commit:** `feat(backend): add offer CRUD with terms`

### Story 2.4 — offer search: filters, sorting, pagination, no N+1

**Files:** create `backend/models/OfferSearch.php`; modify
`backend/views/offer/index.php`

- `OfferSearch extends Offer`, `scenarios()` → `Model::scenarios()`, rules:
  `[['id', 'casino_id'], 'integer']`, `[['title', 'slug'], 'safe']`,
  `[['type'], 'in', 'range' => OfferType::values()]`,
  `[['status'], 'in', 'range' => OfferStatus::values()]`,
  `[['maxWagering'], 'number', 'min' => 0]` where
  `public ?string $maxWagering = null;` is a search-only attribute.
  Out-of-range filter input is dropped instead of reaching the query.
- `search(array $params): ActiveDataProvider`:

```php
// joinWith() does both jobs: LEFT JOINs (so casino.name and
// offer_terms.wagering_multiplier are available to ORDER BY / WHERE) and eager
// loading. It sits OUTSIDE the filter branch, because the sort links are
// offered even when no filter is set — behind the early return, sorting by
// casino.name would hit an unjoined table.
$query = Offer::find()->joinWith(['casino', 'terms']);

$dataProvider = new ActiveDataProvider([
    'query' => $query,
    // Sort and Pagination read Yii::$app->request->queryParams unless told
    // otherwise. Passing $params makes search() self-contained and testable
    // without faking a request — without it, ['sort' => '-casino_id'] is
    // silently ignored.
    'pagination' => ['pageSize' => 20, 'params' => $params],
    'sort' => [
        'attributes' => [
            'id', 'title', 'type', 'status', 'amount', 'expires_at', 'created_at',
            'casino_id' => [
                'asc' => ['casino.name' => SORT_ASC],
                'desc' => ['casino.name' => SORT_DESC],
                'label' => 'Casino',
            ],
            // Keyed after the filter attribute: one grid column carries both
            // the sort link and the filter input. (`DataColumn` has no `sort`
            // property — attempting to point a column at a differently named
            // sort key throws `Setting unknown property`.)
            'maxWagering' => [
                'asc' => ['offer_terms.wagering_multiplier' => SORT_ASC],
                'desc' => ['offer_terms.wagering_multiplier' => SORT_DESC],
                'label' => 'Wagering',
            ],
        ],
        'defaultOrder' => ['created_at' => SORT_DESC],
        'params' => $params,
    ],
]);

if (!($this->load($params) && $this->validate())) {
    return $dataProvider;
}

$query->andFilterWhere(['offer.id' => $this->id])
      ->andFilterWhere(['offer.casino_id' => $this->casino_id])
      ->andFilterWhere(['offer.type' => $this->type])
      ->andFilterWhere(['offer.status' => $this->status])
      ->andFilterWhere(['like', 'offer.title', $this->title])
      ->andFilterWhere(['like', 'offer.slug', $this->slug])
      ->andFilterWhere(['<=', 'offer_terms.wagering_multiplier', $this->maxWagering]);
```

  Column names are table-qualified because `joinWith` pulls both relations into
  the query. Both joins are LEFT, so offers without terms stay visible until the
  wagering filter is actually used.
- Grid filter row: casino dropdown (same `indexBy('id')->column()` list), type
  and status dropdowns from the enum labels, a title text input, and a
  "max wagering" number input. `amount` has `'filter' => false`.

**N+1 evidence, two independent checks.**

*a) Automated, `backend/tests/Unit/Models/OfferSearchTest.php`* — counted from
the framework logger. Note that **each query produces three log records** under
the same category (one `LEVEL_INFO`, plus a profiling begin/end pair), so the
helper filters on the level or the count comes out tripled:

```php
private function dbQueryCount(): int
{
    return count(array_filter(
        Yii::getLogger()->messages,
        static fn (array $message): bool => $message[1] === Logger::LEVEL_INFO
            && str_starts_with((string) $message[2], 'yii\db\Command::'),
    ));
}

public function testListingDoesNotScaleQueriesWithRowCount(): void
{
    Yii::getLogger()->flushInterval = PHP_INT_MAX;   // keep messages for the assertion

    $before = $this->dbQueryCount();
    $models = (new OfferSearch())->search([])->getModels();   // pageSize 20
    $queriesForPage = $this->dbQueryCount() - $before;

    self::assertCount(5, $models);               // five fixture offers

    $touched = [];
    foreach ($models as $offer) {
        self::assertTrue($offer->isRelationPopulated('casino'));
        self::assertTrue($offer->isRelationPopulated('terms'));
        self::assertNotSame('', $offer->casino->name);
        $touched[] = $offer->terms?->wagering_multiplier;   // three of five are null
    }
    self::assertSame(['35.0', '45.0'], array_values(array_filter($touched)));

    self::assertSame($queriesForPage, $this->dbQueryCount() - $before);
    self::assertLessThanOrEqual(4, $queriesForPage);          // COUNT + offers + casinos + terms
}
```

  The `isRelationPopulated()` loop is the precise assertion: drop the eager
  loading and the relations turn lazy, so the post-loop count grows by one per
  row and the test fails.

*b) Measured on the dev database* (steady state, schema cache warm):

```
pageSize=20   queries=4
pageSize=100  queries=4
```

  The four are `SELECT COUNT(*) FROM offer LEFT JOIN casino LEFT JOIN
  offer_terms`, `SELECT offer.* FROM offer LEFT JOIN ...`,
  `SELECT * FROM casino WHERE id IN (...)` and
  `SELECT * FROM offer_terms WHERE offer_id IN (...)` — constant in the number
  of rows rendered. A cold run adds one-off schema introspection
  (`SHOW FULL COLUMNS`, `SHOW CREATE TABLE`, key lookups) per table, which the
  schema cache serves afterwards; measure warm or the numbers mislead.
  A reviewer can reproduce it from the Yii Debug toolbar's **DB** panel once
  Story 3.1 has seeded 30 offers; those numbers go into the README (Story 3.2).

**Acceptance (as built):** `backend/tests/Functional/OfferFilterCest.php` (8
cases) drives real requests: the list renders every offer;
`OfferSearch[type]=free_spins`, `[status]=draft`, `[casino_id]=2` and
`[maxWagering]=40` each narrow it correctly (the last one via the joined
`offer_terms` column); `[type]=cashback` is dropped by the `in` rule — HTTP 200,
full list, no SQL error; `sort=title` / `sort=-title` swap the first row; and
the grid exposes the four filter controls.
`backend/tests/Unit/Models/OfferSearchTest.php` (10 cases) covers the same
filters at the model level plus relational sorting by casino name, the
page size, and the N+1 guard.
Run: `php vendor/bin/codecept run backend/tests`.

**Commit:** `feat(backend): add offer search with filters, sorting and pagination`

---

Epic 3 — Seed data and documentation
------------------------------------

### Story 3.1 — seed command

**Files:** create `console/controllers/SeedController.php`

`php yii seed/offers` populates exactly 5 casinos and 30 offers with their terms,
deterministic and idempotent (a row whose slug already exists is skipped; the
command reports how many were created). Distribution makes the filters and
pagination visible:

- 5 casinos, mixed `is_active`, ratings 3.8–4.7.
- 30 offers — six per casino, so every type and status occurs: as built,
  15 `welcome` / 10 `free_spins` / 5 `no_deposit`, and
  20 `active` / 5 `draft` / 5 `expired`, with 20 publicly visible
  (active and not expired) and 5 already lapsed.
- Terms coherent per type: `welcome` → `min_deposit` set, wagering 35–45;
  `no_deposit` → `min_deposit` null, `max_cashout` set, wagering 60;
  `free_spins` → `valid_days` set, wagering 25. Values straddle 35 so the
  "max wagering" filter shows a difference. 20 of the 30 offers carry a terms
  row; the other 10 (drafts and expired ones) have none, which exercises the
  optional side of the relation.
- Each pair is written through `Offer::saveWithTerms()` after
  `Model::validateMultiple()`, inside one outer transaction — the seed cannot
  drift from the validation rules, and a partial seed is impossible.
- **Backdating.** A lapsed offer cannot be *created*: a past `expires_at` is
  rejected by design. The seed therefore inserts those rows without a date and
  then `updateAttributes(['expires_at' => …])` to a past timestamp,
  reproducing what actually happens — time passing after publication — instead
  of weakening the validator to suit the fixture.

`php yii seed/admin <username> <email> <password>` creates one active user so a
fresh database has a reachable admin panel. Justification: "Admin (behind a
login)" is a requirement and the template's frontend signup needs e-mail
verification, which is file-transport-only in dev.

**Rule change this story forced.** The seed's draft offers carry no terms at
all, and a `welcome` draft was rejected by "Min deposit cannot be blank." —
the conditional rule fired even though `isEmpty()` meant **no terms row would
be written**. The condition is now
`offerType === welcome && !$model->isEmpty()`: state a minimum deposit if you
state any terms, but an offer still being sketched out is free of it. Two unit
tests pin both halves (`testEmptyTermsAreValidEvenForAWelcomeOffer`,
`testPartiallyFilledWelcomeTermsStillRequireTheMinimumDeposit`).

**Acceptance (as built):** `php yii seed/offers` on an empty schema prints
`5 casinos, 30 offers created.` / `Totals: 5 casinos, 30 offers, 20 terms
rows.`; a second run prints `0 casinos, 0 offers created.` with identical
totals. Automated coverage lives in
`common/tests/Unit/Console/SeedControllerTest.php` (8 cases): counts,
idempotency, every type and status present, more than one page of rows, at
least ten publicly visible offers, lapsed rows exist, wagering straddles 35,
no `no_deposit` offer carries a `min_deposit`, and some offers have no terms
row. The console application has no Codeception suite in this template, so the
test lives in the common suite and silences the command's output with an
anonymous subclass rather than changing production code.

**Browser evidence (admin UI, 30 seeded offers):** `/offer/index` shows
"Showing 1-20 of 30 items." with a 2-page pager; page 2 shows "Showing 21-30
of 30 items." with 10 rows; `type=free_spins` → 10 rows, `status=draft` → 5
rows, `maxWagering=35` → 10 rows. The debug toolbar reports **DB 18** on both
page 1 (20 rows) and page 2 (10 rows) — constant in rows rendered. That figure
covers the whole request (session, user identity, debug panels); the listing's
own share is the 4 queries measured in Story 2.4.

**Commit:** `feat(console): add seed command for casinos and offers`

### Story 3.2 — README: schema, admin usage, N+1 evidence

**Files:** modify `README.md`, `phpcs.xml.dist`

README now carries: the three tables with their indexes and checks, why terms
are a 1:1 child table, the validation rules a reader would otherwise have to
reverse-engineer from `rules()`, the seed commands in the setup flow, what the
admin screens offer, and an **"N+1 check"** section with all three pieces of
evidence (measured counts, the guard test, the debug toolbar reading) plus the
two traps: queries are logged three times, and a cold run adds schema
introspection.

`phpcs.xml.dist` gained the directories written since the scaffold
(`common/enums`, `common/tests/Unit`, `backend/tests/*`,
`console/controllers`). `console/migrations` stays out on purpose — migration
class names are snake_case by framework convention and would fail
`Squiz.Classes.ValidClassName`. This also fixed the command the README first
suggested: passing paths on the command line **overrides** the ruleset's file
list and drags in those migrations, so the documented invocation is plain
`php vendor/bin/phpcs --standard=phpcs.xml.dist`.

**Acceptance (as built):** the README's own commands were executed in order on
a wiped database — `php yii migrate/fresh` (5 migrations),
`php yii seed/admin` (user #1), `php yii seed/offers`
(`5 casinos, 30 offers created.`), `php yii_test migrate`,
`php vendor/bin/codecept build`, `php vendor/bin/codecept run --env php-builtin`
→ **OK (140 tests, 376 assertions)**, and
`php vendor/bin/phpcs --standard=phpcs.xml.dist` → clean over 80 files.

**Commit:** `docs: document offers schema, seeding and N+1 check`

---

Epic 4 — Hardening and verification
-----------------------------------

### Story 4.1 — security pass

**Files:** review over the Epic 2 output; fixes land in the touched files.

Checklist, each item verified against the diff:
- no string-interpolated SQL — filters use `andFilterWhere` / bound params, and
  every sortable column comes from the `Sort::$attributes` whitelist;
- no `format => 'raw'` on user data; `Html::encode()` on echoed attributes;
  `terms_note` encoded then `nl2br`; `terms_url` passed through the `url`
  validator on input and rendered with `Html::a()` (so a `javascript:` payload
  never reaches an `href`);
- CSRF: every form is an `ActiveForm` (hidden `_csrf-backend`), every delete is a
  `data-method="post"` link backed by `VerbFilter`;
- `AccessControl` on both controllers denies guests every action;
- mass assignment: `load()`/`loadMultiple()` only fill attributes listed in
  `rules()`; `offer_terms.offer_id` is never in that set;
- `findModel()` does a parameterized primary-key lookup and 404s otherwise;
- the aggregate write is transactional, so a failed terms insert cannot leave an
  orphan offer.

**Acceptance:** `backend/tests/Functional/OfferSecurityCest.php` asserts a guest
is redirected from `offer/index|create|update|delete`, a POST without a CSRF
token is rejected (400), and a stored `<script>` payload appears escaped in both
the grid and the detail page.

**Commit:** `test(backend): cover access control and output escaping`

### Story 4.2 — full verification run

```bash
docker compose up -d
php yii migrate/fresh --interactive=0
php yii seed/admin admin admin@example.com admin123
php yii seed/offers
php yii_test migrate --interactive=0
php vendor/bin/codecept build
php vendor/bin/codecept run --env php-builtin      # template's 44 tests + new suites
php yii serve --port=8081 --docroot=@backend/web    # manual smoke
```

**Acceptance:** all suites green; manual smoke covers create/update/delete of
both entities, an offer with and without terms, every filter, a sort toggle, and
page 2 of the offer grid.

**Commit:** none (verification only); fixes commit under their own story.

---

Story dependency order
----------------------

```mermaid
graph LR
  S11[1.1 casino table] --> S12[1.2 offer table]
  S12 --> S13[1.3 offer_terms table]
  S14[1.4 enums] --> S16[1.6 Offer model]
  S11 --> S15[1.5 Casino model]
  S12 --> S16
  S13 --> S17[1.7 OfferTerms model]
  S14 --> S17
  S16 --> S17
  S15 --> S22[2.2 casino CRUD]
  S17 --> S23[2.3 offer CRUD + terms]
  S21[2.1 nav + gate] --> S22
  S21 --> S23
  S23 --> S24[2.4 offer search]
  S17 --> S31[3.1 seed]
  S24 --> S32[3.2 README]
  S31 --> S32
  S24 --> S41[4.1 security pass]
  S41 --> S42[4.2 verification]
```

1.5 and 1.6/1.7 may proceed in parallel once their tables exist; 2.2 and 2.3
touch disjoint files and can run in parallel after 2.1. 2.4 must follow 2.3
(both edit `backend/views/offer/index.php`).

Open decisions to confirm before Epic 1
---------------------------------------

1. **`amount` semantics.** Required `DECIMAL(12,2)`, interpreted per `type`
   (currency for `welcome`/`no_deposit`, spin count for `free_spins`).
   Alternative: nullable, for offers with no numeric value.
2. **Terms field set.** Seven fields as listed. Not included: `bonus_code`,
   `eligible_games`, `country_restrictions`, `min_odds`. Each is additive —
   a column on `offer_terms` plus a rule and a form field.
3. **`rating` scale.** `DECIMAL(2,1)`, 0.0–5.0.
4. **`expires_at` granularity.** `DATETIME`; plain `DATE` would be simpler if
   offers only ever expire at day boundaries.

Implementation log
------------------

Deviations from the plan as written, and facts established while building. The
story sections above are kept in sync; this is the short list.

| story | status | note |
|-------|--------|------|
| 1.1 casino table | done | `DECIMAL(2,1)` caps the column at `9.9`, so `chk-casino-rating` is what actually enforces 0–5. Verified: `rating = 9.0` → `ERROR 3819`; duplicate slug → `ERROR 1062`. |
| 1.2 offer table | done | A bad `ENUM` value surfaces as `ERROR 1265 Data truncated`, which only rejects under strict mode (MySQL 8.4 default). The model's `in` rule is the primary gate; the column is defence in depth. `EXPLAIN` confirms `idx-offer-status-expires_at` is chosen for the public predicate. |
| 1.3 offer_terms table | done | 1:1 verified structurally: a second row for the same `offer_id` fails on the primary key; deleting a casino cascades two levels (offer → offer_terms). |
| 1.4 enums | done | Added `labelFor()` and the schema drift guard beyond the planned assertions. |
| 1.5 Casino | done | `getOffers()` moved to 1.6 (PHPStan `class.notFound` on the forward reference). |
| 1.6 Offer + OfferQuery | done | Expiry split into two validators; `notExpired()` binds a PHP timestamp instead of MySQL `NOW()`; `getTerms()`, `withTerms()` and `saveWithTerms()` moved to 1.7. |
| 1.7 OfferTerms | done | Picked up the three deferred pieces. `saveWithTerms()` grew an explicit lifecycle for the optional row: skip when empty, delete when blanked, repopulate the relation after commit. Rollback proven against the DB check constraint, not a mock. |
| 2.1 nav + gate | done | Nav only; the 302 gate check belongs to the controllers and moved to 2.2/2.3. Covered by `NavigationCest` (guest vs. signed in). |
| 2.2 casino CRUD | done | `is_active` default had to be set on the create form, not just in `rules()`. Domain fixtures gained `$dataFile = '@common/tests/Support/data/...'` defaults, because `codecept_data_dir()` resolves per suite and the backend suite could not see `common/`'s data files. CSRF rejection (400) turned into its own assertion rather than a test failure. |
| 2.3 offer CRUD | done | Plan's `Model::loadMultiple()` was wrong for two different models (it is a tabular-input helper); replaced with one `load()` per model plus `validateMultiple()`. `actionIndex()` ships a plain `ActiveDataProvider` until 2.4 replaces it. `findModel()` eager-loads casino and terms. |
| 2.4 offer search | done | Three plan-level corrections: `joinWith()` must sit outside the filter branch (relational sorting is offered with no filter set); `Sort`/`Pagination` read request query params unless `'params' => $params` is passed, so the planned `search(['sort' => ...])` was silently ignored; `DataColumn` has no `sort` property, so the wagering sort key was renamed to match the filter attribute. Query count measured at 4, constant for `pageSize` 20 and 100 — count only `LEVEL_INFO` log records, each query logs three. |
| 3.1 seed | done | Seeding real data exposed two rule collisions. A lapsed offer cannot be created (past `expires_at` is rejected), so those rows are inserted dateless and backdated with `updateAttributes()`. A `welcome` draft with no terms was rejected by the min-deposit rule, which now also requires `!isEmpty()` — no terms row, nothing to require. Test lives in the common suite: the console app has no Codeception suite here. |
| 3.2 README | done | Also widened `phpcs.xml.dist` to the directories added since the scaffold; migrations stay excluded (snake_case class names). Passing paths to `phpcs` overrides the ruleset's file list — the README documents the bare invocation. Whole README path re-run from a wiped database: 140 tests, 376 assertions green. |

**Rule adopted from 1.5 onward:** a story may not ship code that fails
`php vendor/bin/phpstan analyse`. Forward references to classes a later story
creates are therefore deferred to that story rather than written early.

**Clock rule (from 1.6):** expiry is compared against PHP's clock everywhere —
validators use `time()`, queries bind `date('Y-m-d H:i:s')`. MySQL `NOW()` is
not used, because `@@session.time_zone` defaults to `SYSTEM` and a DB server in
another zone would disagree with the validators. Observed on this machine:
`yii timeZone=UTC`, PHP and MySQL both at `14:34:06` — agreeing today by
configuration, not by construction.
