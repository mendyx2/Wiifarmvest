<?php

namespace App\Console\Commands;

use App\Jobs\FixMatrixPositionChunk;
use App\Models\Matrix;
use Illuminate\Console\Command;

class FixMatrixPositions extends Command
{
    protected $signature = 'matrix:fix-positions
                            {--chunk=200 : Number of records per dispatched job}
                            {--sync : Process synchronously instead of queuing (slower but no worker needed)}';

    protected $description = 'Backfill the position (left/right) column on the matrices table for stage 1';

    public function handle(): int
    {
        $total = Matrix::where('stage_id', 1)
                       ->whereNull('position')
                       ->where('parent_id', '>', 0)
                       ->count();

        if ($total == 0) {
            $this->info('Nothing to fix — all positions are already set.');
            return 0;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $useSync   = $this->option('sync');
        $batches   = (int) ceil($total / $chunkSize);

        $this->info("Found {$total} rows with null position.");
        $this->info($useSync
            ? "Processing synchronously in chunks of {$chunkSize}..."
            : "Dispatching {$batches} job(s) of up to {$chunkSize} records each..."
        );

        $bar = $this->output->createProgressBar($batches);
        $bar->start();

        Matrix::where('stage_id', 1)
              ->whereNull('position')
              ->where('parent_id', '>', 0)
              ->select('id')
              ->orderBy('id')
              ->chunk($chunkSize, function ($rows) use ($bar, $useSync) {
                  $ids = $rows->pluck('id')->toArray();

                  if ($useSync) {
                      (new FixMatrixPositionChunk($ids))->handle();
                  } else {
                      dispatch(new FixMatrixPositionChunk($ids));
                  }

                  $bar->advance();
              });

        $bar->finish();
        $this->newLine();

        if ($useSync) {
            $this->info('Done. All positions updated.');
        } else {
            $this->info('All jobs dispatched.');
            $this->line('Make sure your queue worker is running:');
            $this->line('  php artisan queue:work --tries=3');
        }

        return 0;
    }
}
