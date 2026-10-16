<?php

namespace App\Services;

use App\Models\Ad;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class AdMediaService
{
    public const IMAGE_MAX_SIDE = 1600;   // sharp on every feed, quick to download on a phone
    public const THUMB_SIZE     = 400;    // shown at 200×200, crisp on retina screens
    public const SHARE_WIDTH    = 1200;   // Facebook / WhatsApp / X large-card size
    public const SHARE_HEIGHT   = 630;

    // Keeps the share picture well under WhatsApp's preview limit (~300 KB)
    private const SHARE_QUALITY = 82;

    public static function thumbName(string $source): string
    {
        return 'thumb_' . pathinfo($source, PATHINFO_FILENAME) . '.jpg';
    }

    public static function shareName(string $source): string
    {
        return 'share_' . pathinfo($source, PATHINFO_FILENAME) . '.jpg';
    }

    /** The real ads folder: under the repo root (the web root), never public_path(). */
    public static function directory(): string
    {
        return dirname(base_path()) . '/' . getFilePath('ads');
    }

    public static function path(string $filename): string
    {
        return self::directory() . '/' . basename($filename);
    }

    public function storeImage(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $filename  = $this->newFilename($extension);

        $this->withMemory(function () use ($file, $extension, $filename) {
            $image = $this->manager()->read($file->getRealPath());
            $image->scaleDown(self::IMAGE_MAX_SIDE, self::IMAGE_MAX_SIDE);

            $encoded = match ($extension) {
                'png'   => $image->toPng(),
                'webp'  => $image->toWebp(85),
                default => $image->toJpeg(85),
            };

            $encoded->save($this->ensureDirectory() . '/' . $filename);
        });

        return $filename;
    }

    public function storeVideo(UploadedFile $file): string
    {
        $filename = $this->newFilename(strtolower($file->getClientOriginalExtension()));
        $file->move($this->ensureDirectory(), $filename);

        return $filename;
    }

    /**
     * Builds the thumbnail and share picture for the ad's picture. Skips the
     * work when both already exist, unless forced. A failure is logged and
     * never blocks saving the ad: the pages fall back to the original file.
     * Returns whether previews were written.
     */
    public function buildPreviews(Ad $ad, bool $force = false): bool
    {
        $source = $ad->pictureSource();
        if (!$source || !is_file(self::path($source))) {
            return false;
        }

        $thumb = self::path(self::thumbName($source));
        $share = self::path(self::shareName($source));

        if (!$force && is_file($thumb) && is_file($share)) {
            return false;
        }

        try {
            $this->withMemory(function () use ($source, $thumb, $share) {
                $image = $this->manager()->read(self::path($source))->blendTransparency('ffffff');

                (clone $image)->cover(self::THUMB_SIZE, self::THUMB_SIZE)->toJpeg(80)->save($thumb);

                $this->manager()
                    ->create(self::SHARE_WIDTH, self::SHARE_HEIGHT)
                    ->fill($this->backdropFor($image))
                    ->place((clone $image)->scaleDown(self::SHARE_WIDTH - 80, self::SHARE_HEIGHT - 64), 'center')
                    ->toJpeg(self::SHARE_QUALITY)
                    ->save($share);
            });
        } catch (\Throwable $e) {
            \Log::error('Ad preview build failed for ad ' . $ad->id . ': ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /** Deletes a stored file and the previews made from it. */
    public function remove(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        foreach ([$filename, self::thumbName($filename), self::shareName($filename)] as $name) {
            $this->unlinkInside($name);
        }
    }

    /**
     * Background for the share picture. Product shots on a plain studio
     * background get that exact colour, so the picture sits seamlessly on it;
     * busy edges (a photo that fills the frame) get a quiet theme neutral.
     */
    private function backdropFor(ImageInterface $image): string
    {
        $probe  = (clone $image)->scaleDown(160, 160);
        $width  = $probe->width();
        $height = $probe->height();

        $pixels = [];
        for ($x = 0; $x < $width; $x += 2) {
            $pixels[] = $probe->pickColor($x, 0);
            $pixels[] = $probe->pickColor($x, $height - 1);
        }
        for ($y = 0; $y < $height; $y += 2) {
            $pixels[] = $probe->pickColor(0, $y);
            $pixels[] = $probe->pickColor($width - 1, $y);
        }

        $rgb   = array_map(fn ($c) => [$c->red()->value(), $c->green()->value(), $c->blue()->value()], $pixels);
        $count = count($rgb);
        $mean  = [0, 0, 0];
        foreach ($rgb as $pixel) {
            for ($i = 0; $i < 3; $i++) {
                $mean[$i] += $pixel[$i] / $count;
            }
        }

        $spread = 0;
        foreach ($rgb as $pixel) {
            $spread += sqrt(($pixel[0] - $mean[0]) ** 2 + ($pixel[1] - $mean[1]) ** 2 + ($pixel[2] - $mean[2]) ** 2) / $count;
        }

        if ($spread < 18) {
            return sprintf('%02x%02x%02x', ...array_map(fn ($v) => (int) round($v), $mean));
        }

        $luminance = 0.299 * $mean[0] + 0.587 * $mean[1] + 0.114 * $mean[2];

        return $luminance > 150 ? 'f2f5f8' : '0f1a14';
    }

    private function unlinkInside(string $filename): void
    {
        $dir  = realpath(self::directory());
        $path = $dir ? realpath($dir . '/' . basename($filename)) : false;

        if ($dir && $path && str_starts_with($path, $dir . DIRECTORY_SEPARATOR) && is_file($path)) {
            @unlink($path);
        }
    }

    private function ensureDirectory(): string
    {
        $dir = self::directory();

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create ' . $dir);
        }

        return $dir;
    }

    private function newFilename(string $extension): string
    {
        return date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    private function manager(): ImageManager
    {
        return new ImageManager(new Driver());
    }

    /** Decoding a large photo needs more than cPanel's usual 128M. */
    private function withMemory(callable $work): void
    {
        @ini_set('memory_limit', '512M');
        $work();
    }
}
