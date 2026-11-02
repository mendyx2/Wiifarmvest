<?php

namespace App\Services;

use App\Models\Matrix;
use App\Models\MatrixStage;
use App\Models\User;

/**
 * Reconnecting overtaken downline when members move up a matrix stage.
 *
 * The live rules are MatrixPlacementService::reconnectImediateDownline() and
 * ::reconnectDownline(), which the cron URLs /recon/{stage} and
 * /reconAll/{stage} run (CronController). This service runs exactly those,
 * so the cron URLs, the ProcessDownlineReconnection job and the
 * matrix:dispatch-reconnections command share one path.
 *
 * It replaces an older draft of this class that nothing called (its own
 * copies of the reconnection rules, half commented out); reconnectDownline()
 * keeps that draft's (User, MatrixStage) signature. Notes Step 124.
 */
class MatrixReconnectionService
{
    public function __construct(private readonly MatrixPlacementService $placement)
    {
    }

    /** Reconnect the immediate downline of every matrix on a stage (/recon/{stage}). Returns how many were checked. */
    public function reconnectImmediate(int $stageId): int
    {
        $count = 0;
        Matrix::where('stage_id', $stageId)->chunk(50, function ($matrices) use (&$count) {
            foreach ($matrices as $matrix) {
                $this->placement->reconnectImediateDownline($matrix);
                $count++;
            }
        });

        return $count;
    }

    /** Reconnect the whole downline of every active matrix on a stage (/reconAll/{stage}). Returns how many were checked. */
    public function reconnectAll(int $stageId): int
    {
        $count = 0;
        Matrix::where('stage_id', $stageId)->where('is_active', true)->chunk(50, function ($matrices) use (&$count) {
            foreach ($matrices as $matrix) {
                $this->placement->reconnectDownline($matrix);
                $count++;
            }
        });

        return $count;
    }

    /** One matrix: its immediate downline, then the rest (what the two stage-wide runs do for it). */
    public function reconnectMatrix(Matrix $matrix): void
    {
        $this->placement->reconnectImediateDownline($matrix);
        $this->placement->reconnectDownline($matrix->fresh());
    }

    /** The older draft's entry point: a member who has just entered $newStage. */
    public function reconnectDownline(User $user, MatrixStage $newStage): void
    {
        $matrix = Matrix::where('user_id', $user->id)->where('stage_id', $newStage->id)->first();
        if ($matrix) {
            $this->reconnectMatrix($matrix);
        }
    }
}
