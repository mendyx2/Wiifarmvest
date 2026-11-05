<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a stockist's enrollment bonus was paid to their upline
 * (App\Services\StockistEnrollmentBonusService). Set once, by the payment
 * itself, so a stockist's bonuses are paid once only. Stockists activated
 * before the bonus was switched on stay empty: paying them is a decision
 * (php artisan stockist:enrollment-bonus). Notes Step 124. Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stockists') && !Schema::hasColumn('stockists', 'enrollment_bonus_paid_at')) {
            Schema::table('stockists', function (Blueprint $table) {
                $table->timestamp('enrollment_bonus_paid_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stockists', 'enrollment_bonus_paid_at')) {
            Schema::table('stockists', fn (Blueprint $table) => $table->dropColumn('enrollment_bonus_paid_at'));
        }
    }
};
