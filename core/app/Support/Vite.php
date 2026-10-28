<?php

namespace App\Support;

use Illuminate\Foundation\Vite as BaseVite;

/**
 * The web root of this project is the repository root (where index.php lives),
 * not core/public. Vite writes its build to <repo>/build so the web server can
 * serve /build/... directly; this reads the manifest from that same folder.
 */
class Vite extends BaseVite
{
    public static function buildPath(string $path = ''): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'build' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    protected function manifestPath($buildDirectory)
    {
        return dirname(base_path()) . '/' . $buildDirectory . '/' . $this->manifestFilename;
    }
}
