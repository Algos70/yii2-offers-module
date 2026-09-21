<?php

declare(strict_types=1);

/**
 * Front controller fallback for PHP's built-in web server.
 *
 * `enablePrettyUrl` means URLs like /offers and /offer/<slug> have no matching
 * file on disk, and the built-in server answers those with a bare 404 unless it
 * is given a router script. Apache and nginx do this with a rewrite rule; this
 * is the same thing for `php yii serve`:
 *
 *   php yii serve --port=8080 --docroot=@frontend/web --router=frontend/web/router.php
 *
 * Existing files (CSS, JS, images) are still served straight from disk.
 */

$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/' . ltrim((string) $path, '/');

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
