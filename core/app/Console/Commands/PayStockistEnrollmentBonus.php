<?php

namespace App\Console\Commands;

use App\Constants\Status;
use App\Models\Stockist;
use App\Services\StockistEnrollmentBonusService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Pay stockists' enrollment bonuses by hand (App\Services\StockistEnrollmentBonusService):
 * one stockist after a failed attempt at activation, or --all for stockists
 * activated before the bonus was switched on. Never pays a stockist twice.
 *
 *   stockist:enrollment-bonus --list              who has not been paid
 *   stockist:enrollment-bonus --all --dry-run     every payee and amount, nothing paid
 *   stockist:enrollment-bonus --all               pay them (asks to confirm the total)
 *   stockist:enrollment-bonus 12                  one stockist
 */
class PayStockistEnrollmentBonus extends Command
{
    protected $signature = 'stockist:enrollment-bonus
                            {stockist? : One stockist id}
                            {--all : Every active stockist whose enrollment bonus is not paid, oldest first}
                            {--include-inactive : With --all, also deactivated stockists}
                            {--dry-run : Show who would be paid what; pay nothing}
                            {--force : Do not ask to confirm the total}
                            {--list : List stockists whose enrollment bonus is not paid}';

    protected $description = "Pay stockists' upline enrollment bonuses (once each), preview them, or list unpaid stockists";

    public function handle(StockistEnrollmentBonusService $bonus): int
    {
        if ($this->option('list') || (!$this->argument('stockist') && !$this->option('all'))) {
            return $this->list();
        }

        $stockists = $this->selection();
        if ($stockists === null) {
            return self::FAILURE;
        }
        if ($stockists->isEmpty()) {
            $this->info('No unpaid stockists to pay.');
            return self::SUCCESS;
        }

        // The plan: exactly what process() will pay, from the same code
        $grand = 0.0;
        $payees = 0;
        foreach ($stockists as $stockist) {
            $plan = $bonus->plan($stockist);
            $grand += $plan['total'];
            $payees += collect($plan['rows'])->where('pay', true)->count();

            $this->line(sprintf('<info>Stockist #%d</info> %s (%s), %s, activated %s', $stockist->id, $stockist->business_name, $stockist->user?->username, $plan['store'], $stockist->created_at ?? 'unknown'));
            if (!$plan['rows']) {
                $this->line('  No upline: nothing to pay (the store PV still goes up the tree).');
                continue;
            }
            $this->table(['Gen', 'Member', 'Amount', ''], collect($plan['rows'])->map(fn ($r) => [
                $r['gen'],
                $r['user']->username,
                '₦' . number_format($r['amount'], 2),
                $r['pay'] ? 'pay' : 'banned: not paid',
            ]));
        }

        $this->line(sprintf('Total: ₦%s to %d payment(s) for %d stockist(s).', number_format($grand, 2), $payees, $stockists->count()));

        if ($this->option('dry-run')) {
            $this->comment('Dry run: nothing was paid.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm(sprintf('Pay ₦%s now?', number_format($grand, 2)))) {
            $this->comment('Nothing was paid.');
            return self::SUCCESS;
        }

        $failed = 0;
        foreach ($stockists as $stockist) {
            try {
                $result = $bonus->process($stockist);
                $this->line($result === null
                    ? sprintf('Stockist #%d: already paid, skipped.', $stockist->id)
                    : sprintf('Stockist #%d: paid ₦%s to %d member(s); %d banned skipped.', $stockist->id, number_format($result['total'], 2), $result['paid'], $result['skipped']));
            } catch (\Throwable $e) {
                // Nothing of this stockist's was paid (one transaction); the others go on
                report($e);
                $this->error(sprintf('Stockist #%d: failed (%s). Nothing was paid for it; run again for this stockist.', $stockist->id, $e->getMessage()));
                $failed++;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** The stockists to pay, or null after an error message. */
    private function selection(): ?Collection
    {
        if ($id = $this->argument('stockist')) {
            $stockist = Stockist::with('user')->find($id);
            if (!$stockist) {
                $this->error('No stockist with that id.');
                return null;
            }
            if ($stockist->enrollment_bonus_paid_at) {
                $this->warn("Already paid on {$stockist->enrollment_bonus_paid_at}. Nothing was paid.");
                return collect();
            }

            return collect([$stockist]);
        }

        $unpaid   = Stockist::with('user')->whereNull('enrollment_bonus_paid_at')->orderBy('id')->get();
        $inactive = $unpaid->filter(fn (Stockist $s) => (int) $s->status !== Status::ACTIVE);
        if ($inactive->isNotEmpty() && !$this->option('include-inactive')) {
            $this->warn('Left out (deactivated): stockist #' . $inactive->pluck('id')->implode(', #') . '. Add --include-inactive to pay them too.');
            $unpaid = $unpaid->diff($inactive);
        }

        return $unpaid->values();
    }

    private function list(): int
    {
        $rows = Stockist::with('user:id,username')->whereNull('enrollment_bonus_paid_at')->orderBy('id')->get()
            ->map(fn (Stockist $s) => [$s->id, $s->user?->username, $s->business_name, $s->isMega() ? 'Mega' : 'Basic', (int) $s->status === Status::ACTIVE ? 'active' : 'deactivated', (string) $s->created_at]);
        $this->table(['Stockist', 'Member', 'Business', 'Store', 'Status', 'Activated'], $rows);
        $this->line($rows->count() . ' stockist(s) without an enrollment bonus. Preview: --all --dry-run; pay: --all (or one id).');

        return self::SUCCESS;
    }
}
