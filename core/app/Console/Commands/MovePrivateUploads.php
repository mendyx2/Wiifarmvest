<?php

namespace App\Console\Commands;

use App\Support\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Moves members' documents out of the public folders (assets/verify,
 * assets/support) into the private folder (App\Support\PrivateFiles).
 * Safe to run again and again: a file already in the private folder is
 * never overwritten, and the old copy is removed only once the new one is
 * there with the same size. The deploy start command runs it.
 */
class MovePrivateUploads extends Command
{
    protected $signature = 'uploads:move-private {--dry-run : Only list what would move}';

    protected $description = "Move members' documents (KYC files, payment proofs, ticket attachments) from the public folders into private storage";

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $moved = $skipped = $failed = 0;

        foreach (array_keys(PrivateFiles::FOLDERS) as $name) {
            $from = PrivateFiles::legacyFolder($name);
            $to   = PrivateFiles::folder($name);
            if (!is_dir($from)) {
                continue;
            }

            foreach (File::allFiles($from, true) as $file) {
                $relative = $file->getRelativePathname();
                $target   = $to . DIRECTORY_SEPARATOR . $relative;

                if (file_exists($target)) {
                    // Already moved: drop the public copy if it is the same file
                    if (!$dry && filesize($target) === $file->getSize()) {
                        File::delete($file->getPathname());
                    }
                    $skipped++;
                    continue;
                }
                if ($dry) {
                    $this->line("would move $name/$relative");
                    $moved++;
                    continue;
                }

                File::ensureDirectoryExists(dirname($target), 0750);
                if (File::copy($file->getPathname(), $target) && filesize($target) === $file->getSize()) {
                    File::delete($file->getPathname());
                    $moved++;
                } else {
                    $this->error("could not move $name/$relative");
                    $failed++;
                }
            }
        }

        $this->info(($dry ? 'Would move' : 'Moved') . " $moved file(s); $skipped already private; $failed failed. Private folder: " . PrivateFiles::root());

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
