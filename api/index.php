<?php

/**
 * Vercel entry point (vercel.json sends every request here). DEMO HOSTING:
 * see notes Step 121 for what does and does not work on Vercel.
 *
 * A Vercel function runs on a read-only disk (only /tmp is writable) and
 * keeps nothing between requests, so this file:
 *  1. sends the public files (assets/, build/) itself: Vercel serves no
 *     static files for this runtime. Same rules as server.php.
 *  2. moves everything Laravel writes to /tmp and picks drivers that need
 *     no disk. Any of these can be overridden in the Vercel project's
 *     environment variables.
 *  3. runs Laravel, like index.php.
 */

$root = dirname(__DIR__);

// 1. Public files
require_once $root . '/core/bootstrap/public_files.php';

$uri    = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$public = wiifarmPublicFile($root, $uri);

if ($public !== null) {
    if ($public['script']) {
        require $public['path'];
        return;
    }

    $types = [
        'css' => 'text/css; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json', 'map' => 'application/json', 'txt' => 'text/plain; charset=utf-8', 'xml' => 'application/xml',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'avif' => 'image/avif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject',
        'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mp3' => 'audio/mpeg', 'pdf' => 'application/pdf',
    ];
    $ext = strtolower(pathinfo($public['path'], PATHINFO_EXTENSION));

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('X-Content-Type-Options: nosniff');
    // Vite's files have a content hash in their names, so they never change
    header(str_starts_with($uri, '/build/assets/')
        ? 'Cache-Control: public, max-age=31536000, immutable'
        : 'Cache-Control: public, max-age=3600, s-maxage=86400');
    header('Content-Length: ' . filesize($public['path']));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        readfile($public['path']);
    }
    return;
}

// 2. Writable paths and disk-free drivers
$defaults = [
    'LOG_CHANNEL'      => 'stderr',
    // Sessions live in the visitor's cookie, encrypted and signed with
    // APP_KEY (this app's web middleware does not encrypt cookies, so an
    // unencrypted cookie session could be edited by the visitor)
    'SESSION_DRIVER'   => 'cookie',
    // Vercel is HTTPS only: the session cookie is never sent over plain HTTP
    'SESSION_SECURE_COOKIE' => 'true',
    // Without a shared cache (Redis), rate limits do not hold between requests
    'CACHE_STORE'      => getenv('REDIS_URL') ? 'redis' : 'array',
    // No worker process: queued jobs run during the request
    'QUEUE_CONNECTION' => 'sync',
    // Vercel's edge is the only way in, so its forwarded headers are trusted
    'TRUSTED_PROXIES'  => '*',
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}
if ((getenv('SESSION_DRIVER') ?: '') === 'cookie') {
    putenv('SESSION_ENCRYPT=true');
    $_ENV['SESSION_ENCRYPT'] = $_SERVER['SESSION_ENCRYPT'] = 'true';
}

// Laravel's own caches: the build writes packages.php; the rest go to /tmp
foreach (['APP_SERVICES_CACHE' => 'services.php', 'APP_PACKAGES_CACHE' => 'packages.php', 'APP_CONFIG_CACHE' => 'config.php', 'APP_ROUTES_CACHE' => 'routes-v7.php', 'APP_EVENTS_CACHE' => 'events.php'] as $key => $file) {
    if (getenv($key) === false && !is_file("$root/core/bootstrap/cache/$file")) {
        putenv("$key=/tmp/$file");
        $_ENV[$key] = $_SERVER[$key] = "/tmp/$file";
    }
}

$storage = '/tmp/storage';
foreach (['app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    if (!is_dir("$storage/$dir")) {
        @mkdir("$storage/$dir", 0700, true);
    }
}

// 3. Laravel
define('LARAVEL_START', microtime(true));

require $root . '/core/vendor/autoload.php';

$app = require_once $root . '/core/bootstrap/app.php';
$app->useStoragePath($storage);
$app->handleRequest(Illuminate\Http\Request::capture());
