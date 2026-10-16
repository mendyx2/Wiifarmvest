<?php

namespace App\Services;

use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\UserStageProgress;
use Illuminate\Support\Facades\DB;
use SplQueue;
class MatrixPlacementService2
{   
    /**
     * Add children to the BFS queue
     */
    public function findDownline_reg(Matrix $matrices, $stage_id = 1)
    {
        $eligible = 0;
        $queue = new SplQueue();
        $count = 0;
        $visited = [];
        $this->enqueueChildren($matrices, $queue, $visited);
           
      

        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();

            if($this->hasAvailableSlot($currentMatrix)){
                 $eligible = $currentMatrix;
                 break;
            }
             $this->enqueueChildren($currentMatrix, $queue, $visited);  
             
             if ($count == 2000) 
                break;
                $count++;
             
        }  
         //dd($eligible);
        return $eligible;
    }

    protected function enqueueChildren(Matrix $matrix, SplQueue $queue, array &$visited): void
    {
         $queue->enqueue($matrix);
        foreach (['left', 'right'] as $position) {
            $userId = $matrix->{$position};
            //dd($userId);
            if ($userId) {
                $childMatrix = Matrix::where('user_id', $userId)
                    ->where('stage_id', 1)
                    ->first();
                    //dd($childMatrix);

                if ($childMatrix && !isset($visited[$childMatrix->id])) {
                    $visited[$childMatrix->id] = true;
                    $queue->enqueue($childMatrix);
                }
            }

        }
    }
   

    /** 
     * Place user in a specific stage
     */
    protected function placeInStage(User $newUser, MatrixStage $stage, ?User $sponsor): array
    {
        // Check if user already exists in this stage
        if ($this->userInStage($newUser, $stage)) {
            return ['status' => 'exists', 'message' => 'User already in this stage'];
        }

        // Try to place under sponsor first
        if ($sponsor) {
            $sponsorMatrix = Matrix::where('user_id', $sponsor->id)
                ->where('stage_id', $stage->id)
                ->first();

            if ($sponsorMatrix && $this->hasAvailableSlot($sponsorMatrix)) {
                return $this->placeUnderParent($newUser, $stage, $sponsorMatrix);
            }
        }

        // Find first available slot in the matrix (BFS)
        $availableParent = $this->findAvailableSlotBFS($stage);

        if (!$availableParent) {
            return ['status' => 'full', 'message' => 'No available slots in this stage'];
        }

        return $this->placeUnderParent($newUser, $stage, $availableParent);
    }

    
   

    /**
     * Check if user exists in stage
     */
    protected function userInStage(User $user, MatrixStage $stage): bool
    {
        return Matrix::where('user_id', $user->id)
            ->where('stage_id', $stage->id)
            ->exists();
    }

    /**
     * Check if matrix position has available slot
     */
    protected function hasAvailableSlot(Matrix $matrix): bool
    {
        return $matrix->left == 0 || $matrix->right == 0;
    }
}