<?php

/**
 * Router for PHP's built-in server (`php -S 0.0.0.0:$PORT server.php`, see
 * Procfile / nixpacks.toml). On Vercel, api/index.php does the same job.
 *
 * The site is served from the repository root, which also holds core/ (the
 * Laravel app: .env, storage/logs, vendor, config) and internal documents.
 * Returning false makes the built-in server send the file itself, and it
 * *runs* .php files, so only the public folders and a few root files are
 * served that way; every other path goes to Laravel, which answers 404.
 * (The old router served any file that existed, e.g. /core/storage/logs/
 * laravel.log, and ran any PHP file under core/ by its URL. See notes
 * Step 106.)
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// The rules for what counts as a public file are in core/bootstrap/public_files.php
require_once __DIR__ . '/core/bootstrap/public_files.php';

if (wiifarmPublicFile(__DIR__, $uri) !== null) {
    return false;
}

// Everything else goes to the Laravel router
require_once __DIR__ . '/index.php';
