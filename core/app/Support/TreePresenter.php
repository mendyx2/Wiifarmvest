<?php

namespace App\Support;

use App\Models\Matrix;
use App\Models\User;
use App\Models\UserStageProgress;

/**
 * The 15-slot genealogy tree (keys a–o from showMatrixTree / showMatrixStageTree)
 * as data for the React <GenealogyTree>. Same fields the old
 * showSingleUserinTree_new() helper put in data-* attributes, but loaded with
 * three batched queries instead of three queries per node.
 */
class TreePresenter
{
    /** Parent slot and side of every non-root slot. */
    public const PARENTS = [
        'b' => ['a', 'left'],  'c' => ['a', 'right'],
        'd' => ['b', 'left'],  'e' => ['b', 'right'],  'f' => ['c', 'left'],  'g' => ['c', 'right'],
        'h' => ['d', 'left'],  'i' => ['d', 'right'],  'j' => ['e', 'left'],  'k' => ['e', 'right'],
        'l' => ['f', 'left'],  'm' => ['f', 'right'],  'n' => ['g', 'left'],  'o' => ['g', 'right'],
    ];

    /**
     * @param  array<string, ?User>  $tree
     * @param  bool  $slots  Empty slots under an existing member link to the distributor form.
     * @return array<string, ?array>
     */
    public static function nodes(array $tree, bool $slots): array
    {
        $users    = collect($tree)->filter();
        $ids      = $users->pluck('id')->all();
        $matrices = Matrix::whereIn('user_id', $ids)->where('stage_id', 1)->get()->keyBy('user_id');
        $sponsors = User::whereIn('id', $users->pluck('ref_by')->filter()->unique())->pluck('username', 'id');
        $stages   = UserStageProgress::with('stage')->whereIn('user_id', $ids)->where('is_completed', false)->get()->keyBy('user_id');

        $out = [];
        foreach (array_merge(['a'], array_keys(self::PARENTS)) as $key) {
            $user = $tree[$key] ?? null;
            if ($user) {
                $m = $matrices->get($user->id);
                $out[$key] = [
                    'username'    => $user->username,
                    'stage'       => $stages->get($user->id)?->stage?->name,
                    'refBy'       => $sponsors[$user->ref_by] ?? null,
                    'pvLeft'      => (float) ($m->pv_left_pairing ?? 0),
                    'pvRight'     => (float) ($m->pv_right_pairing ?? 0),
                    'pvLeftRank'  => (float) ($m->pv_left ?? 0),
                    'pvRightRank' => (float) ($m->pv_right ?? 0),
                    'treeUrl'     => route('user.other.tree', $user->username),
                ];
                continue;
            }

            [$parentKey, $side] = self::PARENTS[$key] ?? [null, null];
            $parent = $parentKey ? ($tree[$parentKey] ?? null) : null;
            $out[$key] = $slots && $parent ? ['slot' => [
                'side'   => $side,
                'parent' => $parent->username,
                'url'    => route('user.distributor.index') . '?parent=' . urlencode($parent->username) . '&position=' . $side,
            ]] : null;
        }

        return $out;
    }

    /** Images the old tree used. */
    public static function images(): array
    {
        return [
            'active' => asset('assets/images/active-user-avatar.png'),
            'empty'  => getImage_tree('assets/images/user/profile/', '120x120', true),
        ];
    }
}
