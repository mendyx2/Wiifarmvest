<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\UserStageProgress;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDownlineReconnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
       
        public Matrix $matrix
    ) {}

    public function handle(MatrixPlacementService $matrixService)
    {
        /*Matrix::where('stage_id', 2)
            ->where('is_active', false)
            ->where('user_id', 37)
            ->chunk(50, function($matrix) {
            foreach($matrix as $mat) {
                if ($mat) {
                    $this->matrixService->reconnectDownline($mat);
                }
            }
         });
        */
         

         $matrixService->reconnectDownline($this->matrix);

         
        //$service->reconnectDownline($this->user, $this->stage);
    }
}
