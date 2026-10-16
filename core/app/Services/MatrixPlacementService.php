<?php

namespace App\Services;

use App\Models\User;
use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\Withdrawal;
use App\Models\UserStageProgress;
use Illuminate\Support\Facades\DB;
use SplQueue;

class MatrixPlacementService
{   

    public function checkStageOne(Matrix $matrix)
    {
        $user = User::find($matrix->user_id);
        $stage = MatrixStage::find($matrix->stage_id);

        if (!$user || !$stage) {
            return false;
        }

        $requiredSlots = $this->calculateRequiredSlots($stage);
        $filledSlots = $this->countFilledSlots($matrix);

        if ($filledSlots < $requiredSlots) {
            return false;
        }

        $processed = false;

        DB::transaction(function () use ($user, $stage, $matrix, &$processed) {
            // Re-fetch with a row lock so concurrent requests can't both enter here
            $freshMatrix = Matrix::where('id', $matrix->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (!$freshMatrix) {
                return; // Already processed by a concurrent request
            }

            $nextStageId = $freshMatrix->stage_id + 1;

            // Guard BEFORE any money moves: stop if user is already in the next stage
            $alreadyPromoted = Matrix::where('user_id', $user->id)
                ->where('stage_id', $nextStageId)
                ->exists();

            if ($alreadyPromoted) {
                $freshMatrix->update(['is_active' => false]);
                return;
            }

            // Mark stage as completed and deactivate matrix
            UserStageProgress::updateOrCreate(
                ['user_id' => $user->id, 'stage_id' => $stage->id],
                ['is_completed' => true, 'completed_at' => now()]
            );

            $freshMatrix->update(['is_active' => false]);

            // Pay commissions only after confirming this is the first promotion
            $trx = getTrx();
            $details = 'Step out Bonus gotten from completing stage:' . $freshMatrix->stage_id;
            
            promotionCommisionMatrix($user, $trx, $details, $freshMatrix->stage_id, 2);

            $details2 = "Referral Bonus gotten from " . $user->username . " Joining stage " . $nextStageId;
            referralStageMAtrix($user->id, $details2, $nextStageId, 1, $trx);

            // Place user in next stage
            Matrix::create([
                'stage_id' => $nextStageId,
                'user_id'  => $user->id,
                'parent_id' => $freshMatrix->parent_id,
                'is_active' => true,
            ]);

            UserStageProgress::updateOrCreate(
                ['user_id' => $user->id, 'stage_id' => $nextStageId],
                ['is_completed' => false]
            );

            $processed = true;
        });

        return $processed;
    }
    public function placeUserInMatrix(User $user, MatrixStage $stage)
    {
        // Check if user already exists in this stage
        if ($user->matrices()->where('stage_id', $stage->id)->exists()) {
            throw new \Exception('User already exists in this matrix stage');
        }
        
        // Find the appropriate parent for placement
        $parent = $this->findAvailableParent($stage);
        
        // Determine position (left or right)
        $position = $this->determinePosition($parent);
        
        // Calculate depth 
        $depth = $parent ? $parent->depth + 1 : 0;
        
        // Create the matrix entry
        $matrix = Matrix::create([
            'stage_id' => $stage->id,
            'user_id' => $user->id,
            'parent_id' => $parent ? $parent->id : null,
            'position' => $position,
            'depth' => $depth,
            'is_active' => true,
        ]);
        
        // Create or update user stage progress
        UserStageProgress::updateOrCreate(
            ['user_id' => $user->id, 'stage_id' => $stage->id],
            ['is_completed' => false]
        );
        
        return $matrix;
    }
    
    protected function findAvailableParent(MatrixStage $stage)
    {
        // Get the root of the matrix (stage 1 has no parent)
        if ($stage->level == 1) {
            return null;
        }
        
        // For other stages, find the first available spot with spillover
        $query = Matrix::where('stage_id', $stage->id)
            ->where('is_active', true)
            ->orderBy('depth');
            
        // Check for available spots (less than width children)
        $parents = $query->get()->filter(function($parent) use ($stage) {
            $childrenCount = $parent->children()->count();
            return $childrenCount < $stage->width;
        });
        
        // If no available parents at current depth, go deeper
        if ($parents->isEmpty()) {
            $deepestParent = $query->orderByDesc('depth')->first();
            return $deepestParent ?? null;
        }
        
        return $parents->first();
    }
    
    protected function determinePosition(?Matrix $parent)
    {
        if (!$parent) {
            return null; // Root node
        }

        // Check which positions are already taken
        $leftTaken = $parent->leftChild() !== null;
        $rightTaken = $parent->rightChild() !== null;
        
        if (!$leftTaken) {
            return 'left';
        }
        
        if (!$rightTaken) {
            return 'right';
        }
        
        // This shouldn't happen if findAvailableParent works correctly
        throw new \Exception('No available positions under this parent');
    } 


    public function checkStageCompletion1(User $user, MatrixStage $stage)
    {        
        $matrix = $user->matrices()->where('stage_id', $stage->id)->where('is_active', true)->first();

        
        if (!$matrix) {
            return false;
        } 
        
        // Check if user has filled all required positions in their downline
        $requiredSlots = $this->calculateRequiredSlots($stage);
        $filledSlots = $this->countFilledSlots($matrix);
        if ($filledSlots >= $requiredSlots) {

            // The user has completed this stage
            // Mark stage as completed
            DB::transaction(function () use ($user, $stage, $matrix) {
                UserStageProgress::updateOrCreate(
                        ['user_id' => $user->id, 'stage_id' => $stage->id],
                        ['is_completed' => true, 'completed_at' => now()]
                );
                 
                // Update matrix status
                $matrix->update(['is_active' => false]);

                $trx = getTrx();

                //Stage out commission bonus for user promotion to stage 2
                $details = 'Step out Bonus gotten from completing stage:'.$matrix->stage_id;
                
                promotionCommisionMatrix($user, $trx, $details, $matrix->stage_id, 2);
                
                
                /*
                deep in the referralStageMAtrix() the system will pick the referral from the users table 'ref_by' 
                */
                $new_stage = $matrix->stage_id + 1;
                $details2 = "Referral Bonus gotten from ".$user->username." Joining stage ".$new_stage;
                
                referralStageMAtrix($user->id, $details2, $new_stage, 1, $trx);
      
                $this->advanceToNextStage($user, $stage);
                



            });  

            return true;
        }
        
        return false;
    }

     protected function advanceToNextStage(User $user, MatrixStage $currentStage)
    {
        $nextStage = MatrixStage::where('level', $currentStage->level + 1)->first();
        
        if (!$nextStage) {
            return false; // No more stages
        }
        
        // Check if user already exists in next stage
        $exists = Matrix::where('user_id', $user->id)
            ->where('stage_id', $nextStage->id)
            ->exists();
            
        if ($exists) {
            return false; // Already in next stage
        }
        
        // Place user in next stage

        $this->placeUserInNextStage($user, $nextStage);
        //dd($nextStage);
       // dd('not working');
    }
  

    protected function placeUserInNextStage(User $user, MatrixStage $stage)
    {
         //dd($stage);
        // Create new matrix entry
        $availableParent = $this->findAvailableParentInStage($stage, $user);
       
        if($availableParent){
            $parent_id = $availableParent->user_id;
            
        }else{
            $parent_id = 0;
        }
        
        //dd($availableParent);
        
        Matrix::create([
            'stage_id' => $stage->id,
            'user_id' => $user->id,
            'parent_id' => $parent_id,
            'is_active' => true,
        ]);
        
        // Record stage progress
        UserStageProgress::create([
            'user_id' => $user->id,
            'stage_id' => $stage->id,
            'is_completed' => false
        ]);

        
        if($parent_id){ 

            
            $level = $stage->level - 1;
            // this is just for one step

            $oldMat = Matrix::where('user_id', $parent_id)
                ->where('stage_id', $level)
                ->first();
            
            if($oldMat){
                
                //if($oldMat->left == $user->id)
                // Update parent's left or right reference
                //dd($oldMat->right);

                $parentUser = User::where('id', $parent_id)->first();
                
                if ($oldMat->left == $user->id && $oldMat->left != 0 && $parentUser) {
                    //dd('left');
                    // Create or update user stage progress
                    Matrix::updateOrCreate(
                        ['user_id' => $parent_id , 'stage_id' => $stage->id],
                        ['left' => $user->id]
                    );




                } elseif($oldMat->right == $user->id && $oldMat->right != 0 && $parentUser) {
                    //dd('right');
                    Matrix::updateOrCreate(
                        ['user_id' => $parent_id , 'stage_id' => $stage->id],
                        ['right' => $user->id]
                    );

                    

                }
                

            }
        }

       
    }

    
    protected function findAvailableParentInStage(MatrixStage $stage, User $user)
    {
        // Get user's upline tree from previous stage
        $uplineTree = $this->getUplineTree($user, $stage->level - 1);
        //dd($uplineTree);  
        // Find first available parent in upline tree
        foreach ($uplineTree as $uplineUser) {
           
            $parentMatrix = Matrix::where('user_id', $uplineUser->user_id)
                ->where('stage_id', $stage->id)
                ->where('is_active', true)
                ->first();
             
            if ($parentMatrix && ($parentMatrix->left == 0 || $parentMatrix->right == 0)) {
              
                return $parentMatrix;
            }
        }
    }

    // receive the user model and stage number of the user previous stage. 

    public function getUplineTree(User $user, int $stageLevel){
        $upline = collect();
        // Get the starting matrix with stage filtering
        $currentMatrix = Matrix::where('user_id', $user->id)
            ->whereHas('stage', function($query) use ($stageLevel) {
                $query->where('level', $stageLevel);
            })
            ->first();

        if (!$currentMatrix) {
            return $upline;
        }
   
        ///////////////////
        while ($currentMatrix) {

            $parentMatrixes = $currentMatrix->parentMatric();
            if (!$parentMatrixes) {
                break;
            } 
            $upline->push($parentMatrixes);
            if(!$parentMatrixes->parent_id){
                break;
            }
            // Move up to parent
            $currentMatrix = $parentMatrixes;
            
          
        }
        
        return $upline;
        
    }

    

    protected function calculateRequiredSlots(MatrixStage $stage)
    {
        // For 2x2 matrix: 2 (direct) + 4 (grand) = 6
        // For 2x3 matrix: 2 (direct) + 4 (grand) + 8 (great grand) = 14
        return $stage->width == 2 ? 6 : 14;
    }
    protected function countFilledSlots(Matrix $matrix)
    {
        $count = 0;
        $stageId = $matrix->stage_id;
        
        // Count direct children
        if ($matrix->left) $count++;
        if ($matrix->right) $count++;
         
        // Helper function to get matrix by user_id and stage
        $getMatrix = function($userId) use ($stageId) {
            return Matrix::where('user_id', $userId)
                ->where('stage_id', $stageId)
                ->first();
        };
        
        // Count grandchildren
        if ($matrix->left && $leftChild = $getMatrix($matrix->left)) {
            if ($leftChild->left) $count++;
            if ($leftChild->right) $count++;
            //dd($count);
        }
        
        if ($matrix->right && $rightChild = $getMatrix($matrix->right)) {
            if ($rightChild->left) $count++;
            if ($rightChild->right) $count++;
            //dd($count);
        }
        
        // For 2x3 matrix, count great-grandchildren
        
            // Left grandchild's children
        
        //if ($stageId == 1 || $stageId == 4 || $stageId == 5){    
            if ($matrix->left && $leftChild) {
                if ($leftChild->left && $leftGrandChild = $getMatrix($leftChild->left)) {
                    if ($leftGrandChild->left) $count++;
                    if ($leftGrandChild->right) $count++;
                }
                if ($leftChild->right && $rightGrandChild = $getMatrix($leftChild->right)) {
                    if ($rightGrandChild->left) $count++;
                    if ($rightGrandChild->right) $count++;
                }
            }
            
            // Right grandchild's children
            if ($matrix->right && $rightChild) {
                if ($rightChild->left && $leftGrandChild = $getMatrix($rightChild->left)) {
                    if ($leftGrandChild->left) $count++;
                    if ($leftGrandChild->right) $count++;
                }
                if ($rightChild->right && $rightGrandChild = $getMatrix($rightChild->right)) {
                    if ($rightGrandChild->left) $count++;
                    if ($rightGrandChild->right) $count++;
                }
            }
       // }
        
       //dd($count);
        return $count;
    }
  

    
    protected function isPositionAvailable(Matrix $parent, string $position): bool
    {
        return $position == 'left' 
            ? is_null($parent->left_id) 
            : is_null($parent->right_id);
    }
    
    public function reconnectImediateDownline(Matrix $userMatrix){
 
        // Get user's matrix in the new stage

        $user = User::find($userMatrix->user_id);

        $newStage = MatrixStage::find($userMatrix->stage_id);
        

        
        $oldNo = $newStage->id - 1; // 1
        
        $oldMatrix = Matrix::where('user_id', $user->id)
            ->where('stage_id', $oldNo )
            ->first(); 
         
        if (!$oldMatrix) {
            return null;
           
        } 

        if(returnJustMatrixOnStageCheck($oldMatrix->right, $userMatrix->stage_id)){

            $childMatrixOnThisStage = returnJustMatrixOnStage($oldMatrix->right, $userMatrix->stage_id);
            if($childMatrixOnThisStage){

                $this->reconnectMember1($userMatrix, $childMatrixOnThisStage, 'right');

            }

        }


        if(returnJustMatrixOnStageCheck($oldMatrix->left, $userMatrix->stage_id)){
           
                $childMatrixOnThisStage_left = returnJustMatrixOnStage($oldMatrix->left, $userMatrix->stage_id);

                if($childMatrixOnThisStage_left){
                    
                     $this->reconnectMember1($userMatrix, $childMatrixOnThisStage_left, 'left');
                }
            
        }
    }
  
    public function reconnectDownline(Matrix $mat){
        
        if($mat->right != null){
            $stage_id =  $mat->stage_id;
            $prevMx = returnJustMatrixOnStage($mat->user_id, $stage_id - 1);
            // right child

            if (returnJustMatrixOnStage($prevMx->right, $stage_id - 1)) {
                $mat_right= returnJustMatrixOnStage($prevMx->right, $stage_id - 1);
                $child = $this->findDownline($mat_right, $stage_id);
                if ($child) {
                   $child_matrix = returnJustMatrixOnStage($child, $stage_id);
                    $this->reconnectMember1($mat, $child_matrix, 'right');
                }
            }
            
         }

         if($mat->left != null){
            $stage_id =  $mat->stage_id;
            $prevMx = returnJustMatrixOnStage($mat->user_id, $stage_id - 1);
            // Left child

            if (returnJustMatrixOnStage($prevMx->left, $stage_id - 1)) {
                $mat_left= returnJustMatrixOnStage($prevMx->left, $stage_id - 1);
                $child_left = $this->findDownline($mat_left, $stage_id);
                if ($child_left) {
                   $child_matrix = returnJustMatrixOnStage($child_left, $stage_id);
                    $this->reconnectMember1($mat, $child_matrix, 'left');
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

            //matrix of my prev stage
            $previousMatrices = Matrix::where('user_id', $user->id)->where('stage_id', $newStage->level - 1)->first();

            //$childFromPrevStage = returnMatrix($previousMatrices->{$position});

            $exists = Matrix::where('user_id', $previousMatrices->{$position})
                            ->where('stage_id', $newStage->level)
                            ->exists();
                    
            if ($exists) {
                $matrix = returnJustMatrixOnStage($previousMatrices->{$position}, $newStage->level);
                   $eligible[] =  $matrix;
                   return $eligible;

            }


        $matrix = returnJustMatrixOnStage($previousMatrices->{$position}, $newStage->level - 1);
        if($matrix){


            $this->enqueueChildren($matrix, $queue, $visited);
           
           $count = 0;
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
               
                $this->enqueueChildren($currentMatrix, $queue, $visited);

                if ($count == 14) {
                  continue; // Don't explore their downline, later make this dept of the matrix from api
                }
                $count++;
            }
        }
        return $eligible;
    }

    /**
     * Add children to the BFS queue
     */

    public function calculateFindDownline(Matrix $matrices)
    {   
        $stage_id = $matrices->stage_id;
        //dd($stage_id);
        $eligible = 0;
        $queue = new SplQueue();
        //$queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $count = 0;
        $visited = [];
        $queue->enqueue($matrices);
        $this->enqueueChildren1($matrices, $queue, $visited);
       
        // Process the queue
        while (!$queue->isEmpty()) {

            $currentMatrix = $queue->dequeue();
           
            if($queue->isEmpty())
                break;
            $this->enqueueChildren1($currentMatrix, $queue, $visited);  

           if ($count == 700) {
                
              break;
            }
            $count++;
             
        } 
        
        return $count;
        //return $currentMatrix;
    }

    protected function enqueueChildren1(Matrix $matrix, SplQueue $queue, array &$visited): void
    {
        //$queue->enqueue($matrix);
        foreach (['left', 'right'] as $position) {
            if($matrix->{$position} > 0){
                $userId = $matrix->{$position};
                //dd($userId);
                if ($userId > 0 ) {
                    $childMatrix = Matrix::where('user_id', $userId)
                        ->where('stage_id', $matrix->stage_id)
                        ->first();
                        //dd($childMatrix);

                    if ($childMatrix){
                        if (!isset($visited[$childMatrix->id])) {
                            $visited[$childMatrix->id] = true;
                            $queue->enqueue($childMatrix);
                        }
                    }

                    
                }
            }

        }

        //dd($queue);
    }

    public function findDownline(Matrix $previousMatrices, $stage_id)
    {
        $eligible = 0;
        $queue = new SplQueue();
        $count = 0;
        $visited = [];
        $this->enqueueChildren($previousMatrices, $queue, $visited);
           
        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();

            if(checkIfUserIsInMatrix_new($currentMatrix->user_id, $stage_id)){
                 $eligible = $currentMatrix->user_id;
                 break;
            }
             $this->enqueueChildren($currentMatrix, $queue, $visited);  
             
             if ($count == 15) 
                break;
                $count++;
             
        }   
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
    // calculate total downline
    public function calculateTotalDownline(Matrix $matrices)
    {   
        $stage_id = $matrices->stage_id;
        //dd($stage_id);
        $eligible = 0;
        $queue = new SplQueue();
        //$queue->setIteratorMode(SplQueue::IT_MODE_DELETE);
        $count = 0;
       $us = $visited = [];
        $queue->enqueue($matrices);
        $this->enqueueChildren1($matrices, $queue, $visited);
       
        // Process the queue
        while (!$queue->isEmpty()) {

            $currentMatrix = $queue->dequeue();
             $us[] =$currentMatrix->user_id;
           
            if($queue->isEmpty())
                break;
            $this->enqueueChildren1($currentMatrix, $queue, $visited);  

           if ($count == 87) {
                
              break;
            }
            $count++;
             
        } 
        
        return $count;
        //return $currentMatrix;
    }

    // calculate total downline
    public function listTotalDownline(Matrix $matrices)
    {    
        $stage_id = $matrices->stage_id;
        //dd($stage_id); 
        $eligible = 0;
        $queue = new SplQueue();
        
        $count = 0;
       $us = $visited = [];
        $queue->enqueue($matrices);
        $this->enqueueChildren1($matrices, $queue, $visited);
       
        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();
             //$user1 =User::find($currentMatrix->user_id);
             //$user2 = User::find($user1->ref_by)->username;

            $us[] =$user1->username. '-----'.$user2;
            if($queue->isEmpty())
                break;
            $this->enqueueChildren1($currentMatrix, $queue, $visited);  

           if ($count == 40000) {
                
              break;
            }
            $count++;        
        }   
        return $us;
        //return $currentMatrix;
    }

    public function checkIfIdIs_A_Downline($user_id, $id)
    {    
       $matrices =  Matrix::where('user_id', $id)->where('stage_id', 1)->first();
        $stage_id = $matrices->stage_id; 
        //dd($stage_id); 
        $eligible = 0;
        $queue = new SplQueue();
        
        $count = 0;
       $us = $visited = [];
        $queue->enqueue($matrices);
        $this->enqueueChildren1($matrices, $queue, $visited);
       
        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();
            if ($currentMatrix->user_id == $user_id) {
                return $currentMatrix;
            }

            //$us[] = $currentMatrix ;
            if($queue->isEmpty())
                break;
            $this->enqueueChildren1($currentMatrix, $queue, $visited);  

           if ($count == 4000) {
                return false;
              //break;
            }
            $count++;        
        }   
        return false;
       
    }


    // calculate total downline
    public function countTotalDownliner(Matrix $matrices)
    {    
        $stage_id = $matrices->stage_id;
        //dd($stage_id); 
        $eligible = 0;
        $queue = new SplQueue();
        
        $count = 0;
       $us = $visited = [];
        $queue->enqueue($matrices);
        $this->enqueueChildren1($matrices, $queue, $visited);
       
        // Process the queue
        while (!$queue->isEmpty()) {
            $currentMatrix = $queue->dequeue();
             
            //$us[] =$currentMatrix->user_id;
            if($queue->isEmpty())
                break;

            $this->enqueueChildren1($currentMatrix, $queue, $visited);  

           if ($count == 70000) {
                
              break;
            }
            $count++;        
        }   
        //return $us;
        return $count;
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

            // if ($oldchildMatrixParent < $parentMatrix->parent_id) {

            //    $parentMatrix->update(['parent_id' => $oldchildMatrixParent]);
            // }

        });
       
       
    }


}