<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Members' documents: KYC photos and ID cards, deposit and withdrawal proofs
 * ("verify") and support-ticket attachments ("support").
 *
 * They are kept on the server outside every public folder (by default
 * core/storage/app/private/uploads, which git ignores), so no address opens
 * them directly. Pages link to them through the signed-in routes
 * (admin.view.attachment, user.view.attachment, the ticket downloads), which
 * hand the file over with respond().
 *
 * They used to live in assets/verify and assets/support, inside the public
 * folder and in the repository. `php artisan uploads:move-private` moves
 * them; until it has run, find() still looks in the old folders.
 */
class PrivateFiles
{
    /** Folder names under the private root, and where each used to be. */
    public const FOLDERS = ['verify' => 'assets/verify', 'support' => 'assets/support'];

    /** The private root: PRIVATE_UPLOADS_PATH (e.g. a mounted volume), else storage/app/private/uploads. */
    public static function root(): string
    {
        return rtrim((string) (config('filesystems.private_uploads') ?: storage_path('app/private/uploads')), '/\\');
    }

    /** Absolute path of one of FOLDERS ("verify" or "support"). */
    public static function folder(string $name): string
    {
        return self::root() . DIRECTORY_SEPARATOR . $name;
    }

    /** The old public folder of one of FOLDERS. */
    public static function legacyFolder(string $name): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . self::FOLDERS[$name];
    }

    /**
     * The real path of a stored file, or null when there is none or the path
     * points anywhere but the private folders. Accepts what the pages encrypt:
     * an absolute path in the private root, or an old "assets/verify/..." /
     * "assets/support/..." path (looked up in the private root first).
     */
    public static function find(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        // Each name is tried in the private folder, then in its old public folder
        $candidates = [$path];
        foreach (self::FOLDERS as $name => $old) {
            $new = str_replace('\\', '/', self::folder($name)) . '/';
            $rest = match (true) {
                str_starts_with($path, $new)                 => substr($path, strlen($new)),
                str_starts_with(ltrim($path, '/'), $old . '/') => substr(ltrim($path, '/'), strlen($old) + 1),
                default                                      => null,
            };
            if ($rest !== null) {
                $candidates = [self::folder($name) . '/' . $rest, self::legacyFolder($name) . '/' . $rest];
            }
        }

        $allowed = [];
        foreach (array_keys(self::FOLDERS) as $name) {
            foreach ([self::folder($name), self::legacyFolder($name)] as $dir) {
                if ($real = realpath($dir)) {
                    $allowed[] = $real . DIRECTORY_SEPARATOR;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real && is_file($real)) {
                foreach ($allowed as $base) {
                    if (str_starts_with($real, $base)) {
                        return $real;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Send a stored file: shown in the browser ($download false, for the
     * "View" links) or saved under $name. 404 when it is not a private file.
     */
    public static function respond(?string $path, bool $download, ?string $name = null): Response
    {
        $real = self::find($path);
        abort_unless($real, 404);

        $headers = ['X-Content-Type-Options' => 'nosniff'];

        // Shown inline only when it is an image or a PDF; anything else is a download
        $mime = mime_content_type($real) ?: 'application/octet-stream';
        if (!$download && !preg_match('#^(image/(png|jpe?g|gif|webp)|application/pdf)$#', $mime)) {
            $download = true;
        }

        $response = $download
            ? response()->download($real, $name ?? slug(gs('site_name')) . '-attachment.' . pathinfo($real, PATHINFO_EXTENSION), $headers)
            : response()->file($real, $headers + ['Content-Type' => $mime]);

        // Personal documents: never kept by a shared cache or proxy
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
