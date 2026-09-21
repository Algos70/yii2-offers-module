Yii2 Offers Module
==================

Yii 2 **advanced** application (2.0.55) with a separate frontend and backend
application, MySQL 8.4 and PHP 8.4. Plain MVC, no frontend framework — the code
is meant to be read.

Layout
------

```
common/     shared code: models (ActiveRecord), config, mail views
console/    console app: migrations (console/migrations) + commands
frontend/   public facing web app  (frontend/web is its docroot)
backend/    admin web app          (backend/web is its docroot, login required)
environments/  templates used by `php init` to generate *-local.php configs
docker/     MySQL init scripts (creates the test schema)
```

Requirements
------------

* PHP >= 8.1 with `pdo_mysql`, `intl`, `mbstring` (verified on PHP 8.4.22)
* Composer 2
* Docker (for MySQL) or a local MySQL 8.x

Setup
-----

```bash
composer install
php init --env=Development --overwrite=All   # generates config/*-local.php, runtime dirs
docker compose up -d                         # MySQL 8.4 on 127.0.0.1:3306
php yii migrate                               # schema for yii2_offers
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
php yii serve --port=8080 --docroot=@frontend/web   # http://localhost:8080
php yii serve --port=8081 --docroot=@backend/web    # http://localhost:8081
```

The backend requires an authenticated user. Frontend signup creates one, but it
sends a verification mail — with `useFileTransport` the mail is written to
`common/runtime/mail` instead of being delivered.

Tests
-----

Codeception. The acceptance suite needs a web server, so use the
`php-builtin` environment, which starts one itself:

```bash
php yii_test migrate --interactive=0   # prepare yii2_offers_test
php vendor/bin/codecept build
php vendor/bin/codecept run --env php-builtin
```
