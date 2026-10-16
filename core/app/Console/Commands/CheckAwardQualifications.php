<?php

namespace App\Console\Commands;

use App\Jobs\CheckUserAwardJob;
use App\Models\Matrix;
use App\Models\User;
use Illuminate\Console\Command;

class CheckAwardQualifications extends Command
{
    protected $signature   = 'award:check-qualifications';
    protected $description = 'Check award qualifications for all active users with a stage-1 matrix entry and dispatch jobs.';
    // Charex this notes help navigate the codes
    public function handle(): int
    {
        $totalDispatched = 0;

        // Get user IDs that have a stage_id=1 matrix entry
        // 
        $matrixUserIds = Matrix::where('stage_id', 1)
                        ->where('pv_left', '>', '1499')
                        ->where('pv_right', '>', '1499')
                        ->pluck('user_id');

        // Chunk through active, profile-complete users who are in the matrix
        User::where('profile_complete', 1)
            ->whereIn('id', $matrixUserIds)
            ->select('id')
            ->chunk(200, function ($users) use (&$totalDispatched) {
                foreach ($users as $user) {

                    CheckUserAwardJob::dispatch($user->id);
                    $totalDispatched++;
                }
                $this->output->write('.');
            });
 
        $this->newLine();
        $this->info("Done. Dispatched {$totalDispatched} CheckUserAwardJob(s).");

        return Command::SUCCESS;
    }
}
