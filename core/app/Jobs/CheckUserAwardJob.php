<?php

namespace App\Jobs;

use App\Models\Award;
use App\Models\Matrix;
use App\Models\UserAward;
use App\Models\User;
use App\Jobs\ProcessAwardAcbJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
 
class CheckUserAwardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 180;

    public function __construct(public int $userId)
    {
    }

    public function handle(): void
    {
        // 1. Load user
        $user = User::find($this->userId);
        if (!$user) {
            return;
        } 

        // 2. Load stage-1 matrix entry
        $matrix = Matrix::where('user_id', $this->userId)->where('stage_id', 1)->first();
        if (!$matrix) {
            return;
        }

        // 3. Load active awards ordered by sort_order then required_total_pv
        $awards = Award::active()
            ->orderBy('sort_order')
            ->orderBy('required_total_pv')
            ->get();

        // 4. Load earned award IDs for this user
        $earnedAwardIds = UserAward::where('user_id', $this->userId)
            ->pluck('award_id')
            ->toArray();

        $leftPv  = (float) $matrix->pv_left;
        $rightPv = (float) $matrix->pv_right;
        $totalPv = $leftPv + $rightPv;

        foreach ($awards as $award) {
            // Skip already earned
            if (in_array($award->id, $earnedAwardIds)) {
                continue;
            }

            // Check package requirement per Proposed Compensation Plan (Page 4)
            $userPv = (float) ($user->project->pv ?? 0);
            if ($award->sort_order == 1 && $userPv < 60) {
                continue; // Emerald requires Builder (60 PV)
            }
            if ($award->sort_order == 2 && $userPv < 105) {
                continue; // Silver requires Booster (105 PV)
            }
            if ($award->sort_order == 3 && $userPv < 150) {
                continue; // Jade requires Executive (150 PV)
            }
            if ($award->sort_order >= 4 && $userPv < 450) {
                continue; // Gold through Global Mega require Mega (450 PV)
            }

            // a. Check left PV
            if ($leftPv < (float) $award->required_left_pv) {
                continue;
            }

            // b. Check right PV
            if ($rightPv < (float) $award->required_right_pv) {
                continue;
            }

            // c. Check total PV
            if ($totalPv < (float) $award->required_total_pv) {
                continue;
            }

            // d. Check prerequisite award in both legs
            if ($award->prerequisite_award_id) {
                $prereqId = $award->prerequisite_award_id;

                // Get direct children legs
                $leftChildUserId  = $matrix->left;
                $rightChildUserId = $matrix->right;

                if (!$leftChildUserId || !$rightChildUserId) {
                    continue;
                }

                $leftLegUsers  = $this->getLegUserIds($leftChildUserId);
                $rightLegUsers = $this->getLegUserIds($rightChildUserId);

                $leftLegHasPrereq = UserAward::where('award_id', $prereqId)
                    ->whereIn('user_id', $leftLegUsers)
                    ->exists();

                $rightLegHasPrereq = UserAward::where('award_id', $prereqId)
                    ->whereIn('user_id', $rightLegUsers)
                    ->exists();

                if (!$leftLegHasPrereq || !$rightLegHasPrereq) {
                    continue;
                }
            }

            // e. All checks pass — create the user award
            $userAward = UserAward::create([
                'user_id'   => $this->userId,
                'award_id'  => $award->id,
                'status'    => 0,
                'earned_at' => now(),
            ]);

            // Dispatch ACB for uplines enrolled in acb_users
            ProcessAwardAcbJob::dispatch($userAward->id);

            // Add to earned list so subsequent awards can chain
            $earnedAwardIds[] = $award->id;
        }
    }

    /**
     * BFS to collect all user_ids in a given leg (subtree).
     * Starts at $rootUserId, uses batch Matrix queries, max 1000 users.
     */
    private function getLegUserIds(int $rootUserId): array
    {
        $collected = [];
        $queue     = [$rootUserId];

        while (!empty($queue) && count($collected) < 1000) {
            $currentBatch = array_splice($queue, 0, 100);

            foreach ($currentBatch as $uid) {
                $collected[] = $uid;
            }

            // Find direct children of this batch in stage 1
            $children = Matrix::where('stage_id', 1)
                ->whereIn('parent_id', Matrix::where('stage_id', 1)
                    ->whereIn('user_id', $currentBatch)
                    ->pluck('id')
                )
                ->pluck('user_id')
                ->toArray();

            foreach ($children as $childId) {
                if (!in_array($childId, $collected)) {
                    $queue[] = $childId;
                }
            }

            if (count($collected) >= 1000) {
                break;
            }
        }

        return $collected;
    }
}
