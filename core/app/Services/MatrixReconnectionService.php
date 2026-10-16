<?php

namespace App\Services;

use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\User;
use App\Models\UserStageProgress;
use Illuminate\Support\Facades\DB;
use SplQueue;

class MatrixReconnectionService
{
    /**
     * Reconnect overtaken downline members when user enters new stage
     */
    

    public function reconnectDownline(User $user, MatrixStage $newStage){
        // Get user's matrix in the new stage
        
        
        $userMatrix = Matrix::where('user_id', $user->id)
            ->where('stage_id', $newStage->id )
            ->first();
        //dd($userMatrix);
       $oldNo = $newStage->id -1;
        
        $oldMatrix = Matrix::where('user_id', $user->id)
            ->where('stage_id', $oldNo )
            ->first(); 
            
        
        if (!$userMatrix) {
            return null;
            //return ['error' => 'User matrix not found in the new stage'];
        }
       

        /* Find all eligible downline members for reconnection
        Also add a clause this should only run if members has reach stage two(2) and above
        */
        
        /*
        if($userMatrix->stage_id){
            $downlineMembers_right = $this->findEligibleDownline($user, $newStage, 'right');
            //dd($downlineMembers);

            if($downlineMembers_right){
                foreach ($downlineMembers_right as $member) {
                   
                    if(!($member->user_id == $userMatrix->right)){
                       $this->reconnectMember1($userMatrix, $member, 'right');
                    } 
                    
                    
                }
            }
        }
    */  
        if($userMatrix){
            //dd(' i am here');
            $downlineMembers_left = $this->findEligibleDownline($user, $newStage, 'right');
            
            if($downlineMembers_left){
                
               
                foreach ($downlineMembers_left as $member) {
                  
                     if($member->user_id == $oldMatrix->left){
                        // dd('i enter');
                       $this->reconnectMember1($userMatrix, $member, 'right');
                    } 
                    //dd($member);
                    
                }
            }
        }
        
        
        if($userMatrix){
            //dd(' i am here');
            $downlineMembers_left = $this->findEligibleDownline($user, $newStage, 'left');
            
            if($downlineMembers_left){
                
               
                foreach ($downlineMembers_left as $member) {
                  
                     if($member->user_id == $oldMatrix->left){
                        // dd('i enter');
                       $this->reconnectMember1($userMatrix, $member, 'left');
                    } 
                    //dd($member);
                    
                }
            }
        }
    }

    /**
     * Find downline members who should be reconnected
     */
    protected function findEligibleDownline(User $user, MatrixStage $newStage, $position): array
    {
        $eligible = [];
        $queue = new SplQueue();

        $visited = [];

        // Start with user's matrices in previous stages
        
         $previousMatrices = Matrix::where('user_id', $user->id)
            ->whereHas('stage', fn($q) => $q->where('level', '<', $newStage->level))
            ->get();
            //dd($previousMatrices);
            
          

        foreach ($previousMatrices as $matrix) {
            
            
            $matrix = returnJustMatrixOnStage($matrix->{$position}, $matrix->stage_id);
            //dd($matrix);
            $this->enqueueChildren($matrix, $queue, $visited);
        }
       // dd($queue);  
        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();
           
           /* 
            
            Check if member is in same or higher stage
            if member is not we keep searchig untill i find a member
            the breaker CONTINUE; 
            */

          if(checkIfUserIsInMatrix_new($currentMatrix->user_id, $newStage->level)){
               //dd('wfeer3r');
                $cMx = returnJustMatrixOnStage($currentMatrix->user_id, $newStage->level);
               
                $eligible[] = $cMx;
                continue; // Don't explore their downline
          }       
           //dd($cMx);
            $this->enqueueChildren($currentMatrix, $queue, $visited);
        }
        return $eligible;
    }

    /**
     * Add children to the BFS queue
     */
    protected function enqueueChildren(Matrix $matrix, SplQueue $queue, array &$visited): void
    {
         $queue->enqueue($matrix);
        foreach (['left', 'right'] as $position) {
            $userId = $matrix->{$position};
            //dd($userId);
            if ($userId) {
                $childMatrix = Matrix::where('user_id', $userId)
                    ->where('stage_id', $matrix->stage_id)
                    ->with('stage')
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
     * Attempt to reconnect a single downline member
     */
    protected function reconnectMember1(Matrix $parentMatrix, Matrix $childMatrix, $position)
    {
        
        // Verify stage eligibility
        if ($childMatrix->stage->level < $parentMatrix->stage->level) {
            return $this->result($childMatrix, 'skipped', 'Member in lower stage');
        }

        // Perform reconnection
        
        DB::transaction(function () use ($parentMatrix, $childMatrix, $position) {
            
            
            // get the old parent to compare at the bottom
            $oldchildMatrixParent = $childMatrix->parent_id;

             // Update child's parent reference

            $childMatrix->update(['parent_id' => $parentMatrix->id]);

            // Update parent's reference
            $parentMatrix->update([$position => $childMatrix->user_id]);


            if ($oldchildMatrixParent < $parentMatrix->parent_id) {

               $parentMatrix->update(['parent_id' => $oldchildMatrixParent]);
            }

        });
       
       
    }

}