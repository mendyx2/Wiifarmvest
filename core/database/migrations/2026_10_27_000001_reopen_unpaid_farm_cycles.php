<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Farm cycles whose "Pay Out" never paid anyone.
 *
 * The admin's Pay Out marked the cycle payout_processed before queueing the
 * payout job, and the job skips a cycle already marked, so no member was
 * credited and the cycle stayed "closed" with the button gone. The job sets
 * status "matured" when it really pays, so "closed" + payout_processed can
 * only be such a cycle. Clearing the flag shows Pay Out again; the actual
 * yield the admin entered is kept. Safe to run twice. See notes Step 105.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('farm_cycles')) {
            return;
        }

        DB::table('farm_cycles')
            ->where('status', 'closed')
            ->where('payout_processed', true)
            ->update(['payout_processed' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Nothing to undo: the flag is set again when the payout runs.
    }
};
