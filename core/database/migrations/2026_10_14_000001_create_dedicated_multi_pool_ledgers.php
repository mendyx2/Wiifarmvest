<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Company Reserve Dedicated Ledger (30.00%)
        if (!Schema::hasTable('company_reserve_ledgers')) {
            Schema::create('company_reserve_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('trx', 40)->index();
                $table->string('source_type', 50)->index();
                $table->decimal('credit', 14, 2)->default(0.00);
                $table->decimal('debit', 14, 2)->default(0.00);
                $table->decimal('balance', 14, 2)->default(0.00);
                $table->string('description', 255)->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }

        // 2. Executive Leadership Pool Dedicated Ledger (10.00%)
        if (!Schema::hasTable('executive_pool_ledgers')) {
            Schema::create('executive_pool_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('trx', 40)->index();
                $table->string('source_type', 50)->index();
                $table->decimal('credit', 14, 2)->default(0.00);
                $table->decimal('debit', 14, 2)->default(0.00);
                $table->decimal('balance', 14, 2)->default(0.00);
                $table->string('description', 255)->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }

        // 3. Operational Expenses Pool Dedicated Ledger (5.00%)
        if (!Schema::hasTable('operational_pool_ledgers')) {
            Schema::create('operational_pool_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('trx', 40)->index();
                $table->string('source_type', 50)->index();
                $table->decimal('credit', 14, 2)->default(0.00);
                $table->decimal('debit', 14, 2)->default(0.00);
                $table->decimal('balance', 14, 2)->default(0.00);
                $table->string('description', 255)->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }

        // 4. Member Compensation Reserve Dedicated Ledger (55.00%)
        if (!Schema::hasTable('compensation_reserve_ledgers')) {
            Schema::create('compensation_reserve_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('trx', 40)->index();
                $table->string('source_type', 50)->index();
                $table->string('sub_pool', 20)->default('CRP')->index(); // CRP, CP, SR, ALLOCATION
                $table->string('bonus_type', 50)->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->decimal('credit', 14, 2)->default(0.00);
                $table->decimal('debit', 14, 2)->default(0.00);
                $table->decimal('balance', 14, 2)->default(0.00);
                $table->string('description', 255)->nullable();
                $table->timestamps();
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compensation_reserve_ledgers');
        Schema::dropIfExists('operational_pool_ledgers');
        Schema::dropIfExists('executive_pool_ledgers');
        Schema::dropIfExists('company_reserve_ledgers');
    }
};
