<?php

/**
 * Which URLs are files the web server may send as they are. Shared by the
 * two entry points: server.php (PHP's built-in server, `php -S`) and
 * api/index.php (Vercel).
 *
 * The site is served from the repository root, which also holds core/ (the
 * Laravel app: .env, storage, vendor, config) and internal documents. Only
 * the public folders and a few root files qualify; everything else goes to
 * Laravel, which answers 404. See notes Steps 106, 119 and 121.
 *
 * Returns null (not a public file), or ['path' => absolute path,
 * 'script' => true when it is the one PHP file allowed to run].
 */
function wiifarmPublicFile(string $root, string $uri): ?array
{
    $publicDirs  = ['assets', 'build'];
    $publicFiles = ['favicon.ico', 'robots.txt', 'sitemap.xml'];
    // Inside assets/: members' uploads that only signed-in pages may open, through
    // Laravel's encrypted download routes (KYC photos and ID cards, ticket
    // attachments, deposit and withdrawal proofs). Never sent as plain files.
    $privateDirs = ['assets/verify/', 'assets/support/', 'assets/images/verify/'];
    // The only PHP that may run directly: the theme colour stylesheet (it
    // accepts nothing but two hex colours)
    $publicScripts = '#^assets/templates/[A-Za-z0-9_-]+/css/color\.php$#';

    if ($uri === '/' || $uri === '' || str_contains($uri, "\0")) {
        return null;
    }

    $root = realpath($root);
    $file = realpath($root . $uri);
    // Must exist, be a file, and stay inside the site root (no ../ escapes)
    if ($root === false || $file === false || !is_file($file) || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
        return null;
    }

    $relative = substr($file, strlen($root) + 1);
    $parts    = explode(DIRECTORY_SEPARATOR, $relative);

    // Never a dotfile or a file in a dot-folder
    foreach ($parts as $part) {
        if ($part === '' || $part[0] === '.') {
            return null;
        }
    }

    $path = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    foreach ($privateDirs as $dir) {
        if (str_starts_with($path, $dir)) {
            return null;
        }
    }

    // No PHP except the theme colour stylesheet
    if (preg_match('/\.(php\d?|phtml|phar)$/i', $relative)) {
        return preg_match($publicScripts, $path) ? ['path' => $file, 'script' => true] : null;
    }

    $public = count($parts) === 1 ? in_array($parts[0], $publicFiles, true) : in_array($parts[0], $publicDirs, true);

    return $public ? ['path' => $file, 'script' => false] : null;
}
