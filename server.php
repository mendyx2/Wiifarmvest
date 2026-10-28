<?php

/**
 * Router for PHP's built-in server (`php -S 0.0.0.0:$PORT server.php`, see
 * Procfile / nixpacks.toml).
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

$publicDirs  = ['assets', 'build'];
// Inside assets/: members' uploads that only signed-in pages may open, through
// Laravel's encrypted download routes (KYC photos and ID cards, ticket
// attachments, deposit and withdrawal proofs). Never sent as plain files.
$privateDirs = ['assets/verify/', 'assets/support/', 'assets/images/verify/'];
$publicFiles = ['favicon.ico', 'robots.txt', 'sitemap.xml'];
// The only PHP the built-in server may run directly: the theme colour
// stylesheet (it accepts nothing but two hex colours)
$publicScripts = '#^assets/templates/[A-Za-z0-9_-]+/css/color\.php$#';

$serveStatic = (function () use ($uri, $publicDirs, $privateDirs, $publicFiles, $publicScripts): bool {
    if ($uri === '/' || str_contains($uri, "\0")) {
        return false;
    }

    $root = realpath(__DIR__);
    $file = realpath(__DIR__ . $uri);
    // Must exist, be a file, and stay inside the site root (no ../ escapes)
    if ($file === false || !is_file($file) || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
        return false;
    }

    $relative = substr($file, strlen($root) + 1);
    $parts    = explode(DIRECTORY_SEPARATOR, $relative);

    // Never a dotfile or a file in a dot-folder
    foreach ($parts as $part) {
        if ($part === '' || $part[0] === '.') {
            return false;
        }
    }

    $path = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    foreach ($privateDirs as $dir) {
        if (str_starts_with($path, $dir)) {
            return false;
        }
    }

    // No PHP except the theme colour stylesheet
    if (preg_match('/\.(php\d?|phtml|phar)$/i', $relative)) {
        return (bool) preg_match($publicScripts, str_replace(DIRECTORY_SEPARATOR, '/', $relative));
    }
    return count($parts) === 1 ? in_array($parts[0], $publicFiles, true) : in_array($parts[0], $publicDirs, true);
})();

if ($serveStatic) {
    return false;
}

// Everything else goes to the Laravel router
require_once __DIR__ . '/index.php';
