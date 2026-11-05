<?php

namespace App\Services;

use App\Models\Matrix;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * The stage-1 binary tree: where a new member goes, and how PV climbs it.
 * One place for both (notes Step 126); the helpers updatePV(),
 * updateProductPV(), upLinePvOnUpgrade() and complete_registration_pin()
 * call it.
 *
 * In stage 1, matrices.parent_id holds the parent's USER id (not a matrix
 * id), and a parent's `left` / `right` hold its children's user ids
 * (0 = free).
 */
class BinaryTree
{
    /** 1 = left leg, 2 = right leg (pv_logs.position) */
    public const LEFT  = 1;
    public const RIGHT = 2;

    public function __construct(private readonly MatrixPlacementService2 $search)
    {
    }

    public static function matrixOf(int $userId): ?Matrix
    {
        return Matrix::where('stage_id', 1)->where('user_id', $userId)->first();
    }

    /** Which leg of $parent the member $childUserId is on, or null if the tree does not say. */
    public static function legOf(Matrix $parent, int $childUserId): ?int
    {
        return match (true) {
            (int) $parent->left === $childUserId && $childUserId > 0  => self::LEFT,
            (int) $parent->right === $childUserId && $childUserId > 0 => self::RIGHT,
            default                                                   => null,
        };
    }

    /**
     * Credit $pv to every upline of $from, on the leg $from is under.
     *
     * - The leg is read from the parent's own left/right. A parent that names
     *   neither (a broken link) is skipped and logged; it used to get the PV
     *   on its right leg.
     * - Each credit is one atomic SQL increment (pv_left = pv_left + ?), so
     *   two purchases or registrations at the same moment can no longer
     *   overwrite each other's PV.
     * - A loop in the tree ends the climb instead of running forever.
     *
     * @return int how many uplines were credited
     */
    public function creditPv(User $from, float $pv, string $details, ?int $maxLevels = null): int
    {
        if ($pv <= 0) {
            return 0;
        }

        $matrix = self::matrixOf($from->id);
        if (!$matrix || !$matrix->parent_id) {
            return 0;
        }

        $credited = 0;
        $level    = 0;
        $child    = (int) $from->id;
        $parentId = (int) $matrix->parent_id;
        $seen     = [$child => true];

        while ($parentId > 0 && !isset($seen[$parentId]) && ($maxLevels === null || $level < $maxLevels)) {
            $seen[$parentId] = true;
            $parent          = self::matrixOf($parentId);
            if (!$parent) {
                break;
            }

            $leg = self::legOf($parent, $child);
            if ($leg === null) {
                Log::error('[BinaryPV] Member is not the left or right child of their parent: PV not credited to this upline.', [
                    'child_user_id' => $child, 'parent_user_id' => $parentId, 'parent_left' => $parent->left, 'parent_right' => $parent->right, 'pv' => $pv,
                ]);
            } else {
                $side = $leg === self::LEFT ? 'left' : 'right';
                Matrix::whereKey($parent->id)->toBase()->incrementEach(["pv_{$side}" => $pv, "pv_{$side}_pairing" => $pv]);
                pvLog($parent->user_id, $pv, $leg, '+', $details);
                $credited++;
            }

            $child    = (int) $parent->user_id;
            $parentId = (int) $parent->parent_id;
            $level++;
        }

        return $credited;
    }

    /**
     * Find and take a free slot for a new member.
     *
     * - A chosen parent and leg: that slot. If it was taken meanwhile, the
     *   first free slot further down that same leg (the member stays on the
     *   side that was chosen; they used to be left outside the tree).
     * - No choice, or the chosen parent is not in the tree: the first free
     *   slot under the sponsor, level by level, left first.
     * - A slot is taken with one conditional UPDATE (only if it is still
     *   free), so two members can never get the same slot; a lost race
     *   searches again.
     *
     * @return array{0: int, 1: string} [parent user id, 'left'|'right'], or [0, 'root'] when there is no tree to join
     */
    public function placeNewMember(User $user, int $chosenParentId, ?string $chosenLeg, int $sponsorId): array
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            [$node, $leg] = $this->freeSlot($chosenParentId, $chosenLeg, $sponsorId);
            if (!$node) {
                if (self::matrixOf($sponsorId) || $chosenParentId > 0) {
                    Log::error('[BinaryPlacement] No free slot found: member placed as a root.', ['user_id' => $user->id, 'sponsor_id' => $sponsorId, 'chosen_parent' => $chosenParentId]);
                }
                return [0, 'root'];
            }

            $taken = Matrix::whereKey($node->id)
                ->where(fn ($q) => $q->where($leg, 0)->orWhereNull($leg))
                ->update([$leg => $user->id]);

            if ($taken) {
                return [(int) $node->user_id, $leg];
            }
        }

        throw new \RuntimeException('Could not find a free place in the tree for member #' . $user->id . ' (too many members joining at once). Please try again.');
    }

    /** @return array{0: ?Matrix, 1: ?string} */
    private function freeSlot(int $chosenParentId, ?string $chosenLeg, int $sponsorId): array
    {
        if ($chosenParentId > 0 && in_array($chosenLeg, ['left', 'right'], true)) {
            $parent = Matrix::where('stage_id', 1)->where('user_id', $chosenParentId)->where('is_active', 1)->first();
            if ($parent) {
                if ((int) $parent->{$chosenLeg} === 0) {
                    return [$parent, $chosenLeg];
                }
                // Taken: go down the chosen leg
                $child = self::matrixOf((int) $parent->{$chosenLeg});
                $node  = $child ? $this->search->findDownline_reg($child, 1) : null;
                if ($node) {
                    return [$node, (int) $node->left === 0 ? 'left' : 'right'];
                }
            }
        }

        $sponsor = $sponsorId > 0 ? self::matrixOf($sponsorId) : null;
        $node    = $sponsor ? $this->search->findDownline_reg($sponsor, 1) : null;

        return $node ? [$node, (int) $node->left === 0 ? 'left' : 'right'] : [null, null];
    }
}
