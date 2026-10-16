<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDownlineReconnection;
use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;


use Illuminate\Console\Command;

class DispatchReconnectionJobs extends Command
{
     protected $signature = 'matrix:dispatch-reconnections 
                            {--stage= : Specific stage level to process}';
                            
    protected $description = 'Dispatch downline reconnection jobs to the queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Matrix::where('stage_id', 2)
            ->where('is_active', true)
            ->where('user_id', 37)
            ->chunk(50, function($matrix) {
            foreach($matrix as $mat) {
                if ($mat) {
                    $matrixService->reconnectDownline($mat);
                }
            }
        });
    }
}
