<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Services\AdMediaService;
use Illuminate\Console\Command;

class BuildAdPreviews extends Command
{
    protected $signature = 'ads:previews {--force : Rebuild previews that already exist}';

    protected $description = 'Build the thumbnail and link-preview picture of every ad that is missing them';

    public function handle(AdMediaService $media)
    {
        $built = 0;
        Ad::orderBy('id')->each(function (Ad $ad) use ($media, &$built) {
            if ($media->buildPreviews($ad, (bool) $this->option('force'))) {
                $built++;
            }
        });

        $this->info("Built previews for {$built} ad(s).");

        return self::SUCCESS;
    }
}
