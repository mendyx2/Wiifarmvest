<?php

namespace App\Console\Commands;

use App\Models\Stockist;
use App\Services\StockistEnrollmentBonusService;
use Illuminate\Console\Command;

/**
 * Pay one stockist's enrollment bonuses by hand (App\Services\StockistEnrollmentBonusService):
 * after a failed attempt at activation, or for a stockist activated before the
 * bonus was switched on. Never pays twice. --list shows who has not been paid.
 */
class PayStockistEnrollmentBonus extends Command
{
    protected $signature = 'stockist:enrollment-bonus {stockist? : The stockist id} {--list : List stockists whose enrollment bonus is not paid}';

    protected $description = "Pay a stockist's upline enrollment bonuses (once), or list unpaid stockists";

    public function handle(StockistEnrollmentBonusService $bonus): int
    {
        if ($this->option('list') || !$this->argument('stockist')) {
            $rows = Stockist::with('user:id,username')->whereNull('enrollment_bonus_paid_at')->orderBy('id')
                ->get()->map(fn (Stockist $s) => [$s->id, $s->user?->username, $s->business_name, $s->isMega() ? 'Mega' : 'Basic', (string) $s->created_at]);
            $this->table(['Stockist', 'Member', 'Business', 'Store', 'Activated'], $rows);
            $this->line($rows->count() . ' stockist(s) without an enrollment bonus. Pay one with: php artisan stockist:enrollment-bonus {id}');
            return self::SUCCESS;
        }

        $stockist = Stockist::find($this->argument('stockist'));
        if (!$stockist) {
            $this->error('No stockist with that id.');
            return self::FAILURE;
        }

        $result = $bonus->process($stockist);
        if ($result === null) {
            $this->warn('Already paid on ' . $stockist->fresh()->enrollment_bonus_paid_at . '. Nothing was paid.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Paid ₦%s to %d upline member(s); %d banned member(s) skipped.', number_format($result['total'], 2), $result['paid'], $result['skipped']));

        return self::SUCCESS;
    }
}
