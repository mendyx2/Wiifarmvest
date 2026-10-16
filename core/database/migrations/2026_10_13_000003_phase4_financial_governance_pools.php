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
        if (!Schema::hasTable('company_pool_ledgers')) {
            Schema::create('company_pool_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('trx', 40)->index();
                $table->string('source_type', 50)->index(); // deposit, registration, order, compensation_payout
                $table->string('description', 255)->nullable();
                $table->decimal('gross_amount', 14, 2)->default(0.00);
                
                // Allocation amounts for current entry
                $table->decimal('company_reserve_amount', 14, 2)->default(0.00);       // 30%
                $table->decimal('executive_pool_amount', 14, 2)->default(0.00);        // 10%
                $table->decimal('operational_pool_amount', 14, 2)->default(0.00);      // 5%
                $table->decimal('compensation_reserve_amount', 14, 2)->default(0.00);  // 55%

                // Running balances after this entry
                $table->decimal('company_reserve_balance', 14, 2)->default(0.00);
                $table->decimal('executive_pool_balance', 14, 2)->default(0.00);
                $table->decimal('operational_pool_balance', 14, 2)->default(0.00);
                $table->decimal('compensation_reserve_balance', 14, 2)->default(0.00);

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
        Schema::dropIfExists('company_pool_ledgers');
    }
};
