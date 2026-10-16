<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\LeadershipBonusLog;
use App\Models\LeadershipLeader;
use App\Models\LeadershipSetting;
use App\Models\MatchingBonusLog;
use App\Models\Matrix;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadershipBonusService
{
    public const REMARK = 'leadership_bonus';
    public const WALLET = 'leadership_bonus';

    /** Matching runs on the stage-1 binary matrix, so PV and the upline walk use it too. */
    public const STAGE = 1;

    /** Stops the walk on a corrupt (cyclic) tree instead of looping forever. */
    private const MAX_DEPTH = 5000;

    public function __construct(private readonly WalletService $wallet)
    {
    }

  
    public function payForMatching(int $matchingLogId): int
    {
        $log = MatchingBonusLog::find($matchingLogId);
        if (!$log || $log->leadership_processed_at) {
            return 0;
        }

        $settings = LeadershipSetting::current();
        $earnedAt = $log->processed_at ?? $log->created_at;
        $amount   = $this->share((float) $log->total_bonus, $settings);

        if (!$settings->isActive() || !$settings->enabled_at || $earnedAt->lt($settings->enabled_at) || $amount <= 0) {
            $this->claim($log->id);   // nothing is due; mark it so the sweep stops finding it
            return 0;
        }

        // Read-only work before the claim: who sits above the earner, and who qualifies
        $payees = $this->payees((int) $log->user_id, $settings);
        $earner = User::find($log->user_id);

        return DB::transaction(function () use ($log, $settings, $amount, $payees, $earner) {
            if (!$this->claim($log->id)) {
                return 0;   // another run already paid this matching bonus
            }

            foreach ($payees as $payee) {
                if ($payee['qualify']) {
                    $this->qualify($payee['matrix'], $settings);
                }

                $trx = getTrx();

                LeadershipBonusLog::create([
                    'leader_id'             => $payee['user_id'],
                    'source_user_id'        => $log->user_id,
                    'matching_bonus_log_id' => $log->id,
                    'level'                 => $payee['level'],
                    'leg'                   => $payee['leg'],
                    'matching_amount'       => $log->total_bonus,
                    'percentage'            => $settings->percentage,
                    'amount'                => $amount,
                    'trx'                   => $trx,
                ]);

                    $this->wallet->credit(
                    $payee['user_id'],
                    $amount,
                    self::REMARK,
                    $settings->percentageLabel() . ' of @' . ($earner?->username ?? 'member') . '\'s matching bonus of '
                        . showAmount($log->total_bonus, currencyFormat: false)
                        . ' (' . ($payee['leg'] ? $payee['leg'] . ' leg, ' : '') . 'level ' . $payee['level'] . ')',
                    $trx,
                    self::WALLET,
                );

                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    payoutAmount: $amount,
                    trx: $trx,
                    description: "Leadership bonus paid to leader #{$payee['user_id']}",
                    subPool: \App\Models\CompensationReserveLedger::SUB_POOL_CRP,
                    bonusType: 'leadership_bonus',
                    userId: $payee['user_id']
                );
            }

            return count($payees);
        });
    }

   
    public function processPending(): array
    {
        $result = ['qualified' => $this->qualifyEligible(), 'paid' => 0, 'failed' => 0, 'notified' => 0];

        MatchingBonusLog::whereNull('leadership_processed_at')->chunkById(100, function ($logs) use (&$result) {
            foreach ($logs as $log) {
                try {
                    $result['paid'] += $this->payForMatching($log->id);
                } catch (\Throwable $e) {
                    $result['failed']++;
                    Log::error('[Leadership] Paying leaders failed.', ['matching_bonus_log_id' => $log->id, 'error' => $e->getMessage()]);
                }
            }
        });

        $result['notified'] = $this->notifyNewLeaders();

        return $result;
    }

   
    public function notifyNewLeaders(): int
    {
        $settings = LeadershipSetting::current();
        $sent     = 0;

        LeadershipLeader::active()->whereNull('notified_at')->chunkById(100, function ($leaders) use ($settings, &$sent) {
            foreach ($leaders as $leader) {
                $claimed = LeadershipLeader::whereKey($leader->id)->whereNull('notified_at')
                    ->update(['notified_at' => now()]);
                if ($claimed) {
                    $this->congratulate($leader, $settings);
                    $sent++;
                }
            }
        });

        return $sent;
    }

    /** Make every member who has reached the PV a leader. Returns how many joined. */
    public function qualifyEligible(): int
    {
        $settings = LeadershipSetting::current();
        if (!$settings->isActive()) {
            return 0;
        }

        $count = 0;
        $this->eligibleQuery((float) $settings->required_pv)
            ->chunkById(200, function ($matrices) use ($settings, &$count) {
                foreach ($matrices as $matrix) {
                    $count += (int) $this->qualify($matrix, $settings);
                }
            });

        return $count;
    }

   
    public function eligibleCount(float $requiredPv): int
    {
        return $requiredPv > 0 ? $this->eligibleQuery($requiredPv)->count() : 0;
    }

    /** A leader's share of one matching bonus. */
    public function share(float $matchingAmount, LeadershipSetting $settings): float
    {
        return round($matchingAmount * (float) $settings->percentage / 100, 2);
    }

    public function totalPv(?Matrix $matrix): float
    {
        return $matrix ? (float) $matrix->pv_left + (float) $matrix->pv_right : 0.0;
    }

    public function matrixFor(int $userId): ?Matrix
    {
        return Matrix::where('user_id', $userId)->where('stage_id', self::STAGE)->first();
    }

    // ── internals ───────────────────────────────────────────────────────────

    /** Claim a matching bonus for leadership exactly once. */
    private function claim(int $matchingLogId): bool
    {
        return MatchingBonusLog::whereKey($matchingLogId)
            ->whereNull('leadership_processed_at')
            ->update(['leadership_processed_at' => now()]) === 1;
    }

    /**
     * The members above the earner who get paid: active leaders, plus anyone who
     * has reached the PV but hasn't been swept into the leaders list yet.
     * Suspended leaders and banned members are skipped.
     *
     * @return list<array{user_id: int, matrix: Matrix, level: int, leg: ?string, qualify: bool}>
     */
    private function payees(int $earnerId, LeadershipSetting $settings): array
    {
        $chain = $this->uplines($earnerId, $settings->max_levels);
        if (!$chain) {
            return [];
        }

        $ids      = array_map(fn ($link) => (int) $link['matrix']->user_id, $chain);
        $leaders  = LeadershipLeader::whereIn('user_id', $ids)->get()->keyBy('user_id');
        $statuses = User::whereIn('id', $ids)->pluck('status', 'id');
        $required = (float) $settings->required_pv;
        $payees   = [];

        foreach ($chain as $link) {
            $userId = (int) $link['matrix']->user_id;

            if ((int) ($statuses[$userId] ?? Status::USER_BAN) !== Status::USER_ACTIVE) {
                continue;
            }

            $leader = $leaders->get($userId);
            if ($leader && !$leader->status) {
                continue;
            }
            if (!$leader && $this->totalPv($link['matrix']) < $required) {
                continue;
            }

            $payees[] = $link + ['user_id' => $userId, 'qualify' => !$leader];
        }

        return $payees;
    }

    /**
     * Walk up the binary tree from a member: their parent is level 1, the
     * parent's parent level 2, and so on, with the leg each step came up.
     *
     * @return list<array{matrix: Matrix, level: int, leg: ?string}>
     */
    private function uplines(int $userId, int $maxLevels): array
    {
        $current = $this->matrixFor($userId);
        $seen    = [$userId => true];
        $chain   = [];
        $level   = 0;

        while ($current && (int) $current->parent_id > 0) {
            $level++;
            if (($maxLevels > 0 && $level > $maxLevels)) {
                break;
            }
            if ($level > self::MAX_DEPTH || isset($seen[(int) $current->parent_id])) {
                Log::warning('[Leadership] Binary tree loop or runaway depth; stopped walking.', ['user_id' => $userId, 'at' => $current->user_id]);
                break;
            }

            $parent = $this->matrixFor((int) $current->parent_id);
            if (!$parent) {
                break;
            }

            $leg = match ((int) $current->user_id) {
                (int) $parent->left  => 'left',
                (int) $parent->right => 'right',
                default              => null,
            };

            $chain[] = ['matrix' => $parent, 'level' => $level, 'leg' => $leg];
            $seen[(int) $parent->user_id] = true;
            $current = $parent;
        }

        return $chain;
    }

    /** Stage-1 matrices whose PV meets the target, of active members who aren't leaders yet. */
    private function eligibleQuery(float $requiredPv)
    {
        return Matrix::where('stage_id', self::STAGE)
            ->whereRaw('(pv_left + pv_right) >= ?', [$requiredPv])
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('leadership_leaders')
                ->whereColumn('leadership_leaders.user_id', 'matrices.user_id'))
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('users')
                ->whereColumn('users.id', 'matrices.user_id')
                ->where('users.status', Status::USER_ACTIVE));
    }

    /**
     * Make a member a leader. insertOrIgnore on the unique user_id makes this a
     * no-op for anyone already listed, including suspended leaders. The notice
     * goes out on the next leadership:process run (notifyNewLeaders).
     */
    private function qualify(Matrix $matrix, LeadershipSetting $settings): bool
    {
        return LeadershipLeader::query()->insertOrIgnore([
            'user_id'      => $matrix->user_id,
            'pv_left'      => $matrix->pv_left,
            'pv_right'     => $matrix->pv_right,
            'required_pv'  => $settings->required_pv,
            'status'       => true,
            'qualified_at' => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]) > 0;
    }

    private function congratulate(LeadershipLeader $leader, LeadershipSetting $settings): void
    {
        $user = User::find($leader->user_id);
        if (!$user) {
            return;
        }

        $total = (float) $leader->pv_left + (float) $leader->pv_right;

        try {
            notify($user, 'LEADERSHIP_QUALIFIED', [
                'pv_left'     => showAmount($leader->pv_left, currencyFormat: false),
                'pv_right'    => showAmount($leader->pv_right, currencyFormat: false),
                'total_pv'    => showAmount($total, currencyFormat: false),
                'required_pv' => showAmount($leader->required_pv, currencyFormat: false),
                'percentage'  => rtrim($settings->percentageLabel(), '%'),
                'levels'      => $settings->levelsLabel(),
                'link'        => route('user.leadership'),
            ], createLog: false);
        } catch (\Throwable $e) {
            Log::error("[Leadership] Qualification notice to user #{$user->id} not sent: " . $e->getMessage());
        }

        $log                    = new NotificationLog();
        $log->user_id           = $user->id;
        $log->sender            = 'system';
        $log->sent_from         = mb_substr((string) gs('site_name'), 0, 40);
        $log->sent_to           = mb_substr((string) $user->username, 0, 40);
        $log->notification_type = 'leadership';
        $log->subject           = 'You are now a Leader';
        $log->message           = 'Your binary PV reached ' . showAmount($total, currencyFormat: false)
            . '. You now earn ' . $settings->percentageLabel() . ' of every matching bonus your binary team earns ('
            . $settings->levelsLabel() . ').';
        $log->save();
    }
}
