<?php
namespace App\Services;

use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\User;
use App\Models\UserEarning;
use Illuminate\Support\Facades\DB;

class MatrixRewardService
{
    public function checkCompletionsAndAllocateRewards()
    {
        DB::transaction(function () {
            $stages = MatrixStage::orderBy('level')->get();
            
            foreach ($stages as $stage) {
                $this->processStageCompletions($stage);
            }
        });
    }

    protected function processStageCompletions(MatrixStage $stage)
    {
        // Get all active matrices in this stage
        $matrices = Matrix::where('stage_id', $stage->id)
            ->where('is_active', true)
            ->with(['user', 'stage'])
            ->get();

        foreach ($matrices as $matrix) {
            if ($this->hasCompletedStage($matrix, $stage)) {
                $this->markStageComplete($matrix);
                $this->allocateRewards($matrix);
            }
        }
    }

    protected function hasCompletedStage(Matrix $matrix, MatrixStage $stage): bool
    {
        $requiredSlots = $this->calculateRequiredSlots($stage);
        $filledSlots = $this->countFilledSlots($matrix);
        
        return $filledSlots >= $requiredSlots;
    }

    protected function calculateRequiredSlots(MatrixStage $stage): int
    {
        // For 2x2 matrix: 2 direct + 4 grandchildren = 6
        // For 2x3 matrix: 2 direct + 4 grandchildren + 8 great-grandchildren = 14
        return $stage->width === 2 ? 6 : 14;
    }

    protected function countFilledSlots(Matrix $matrix): int
    {
        $count = 0;
        $stageId = $matrix->stage_id;
        
        // Count direct children
        if ($matrix->left) $count++;
        if ($matrix->right) $count++;
        
        // Helper to get matrix by user_id and stage
        $getMatrix = fn($userId) => Matrix::where('user_id', $userId)
            ->where('stage_id', $stageId)
            ->first();

        // Count grandchildren
        if ($matrix->left && $leftChild = $getMatrix($matrix->left)) {
            if ($leftChild->left) $count++;
            if ($leftChild->right) $count++;
        }
        
        if ($matrix->right && $rightChild = $getMatrix($matrix->right)) {
            if ($rightChild->left) $count++;
            if ($rightChild->right) $count++;
        }
        
        // For 2x3 matrix, count great-grandchildren
        if ($matrix->stage->width === 3) {
            // Left grandchild's children
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
        }
        
        return $count;
    }

    protected function markStageComplete(Matrix $matrix)
    {
        $matrix->update(['is_active' => false]);
        
        // Mark stage as completed in user's progress
        $matrix->user->stageProgress()
            ->updateOrCreate(
                ['stage_id' => $matrix->stage_id],
                ['is_completed' => true, 'completed_at' => now()]
            );
    }

    protected function allocateRewards(Matrix $matrix)
    {
        $rewardAmount = $matrix->stage->price; // Or your reward calculation
        
        UserEarning::create([
            'user_id' => $matrix->user_id,
            'amount' => $rewardAmount,
            'type' => 'stage_completion',
            'stage_id' => $matrix->stage_id,
            'description' => 'Stage completion reward for '.$matrix->stage->name,
        ]);
        
        // Update user's balance
        $matrix->user->increment('balance', $rewardAmount);
    }
    
}
