<?php

namespace App\Contracts;

use App\Models\Matrix;
use App\Models\MatchingBonusLog;

interface MatchingBonusServiceInterface
{
    public function processMatrix(Matrix $matrix): ?MatchingBonusLog;

    public function calculateMatches(float $pvLeft, float $pvRight): int;

    public function hasAutoshipFulfilled(int $userId): bool;
}
