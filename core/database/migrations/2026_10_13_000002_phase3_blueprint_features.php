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
        // 1. Add sponsor_user_id to stockists table
        if (Schema::hasTable('stockists') && !Schema::hasColumn('stockists', 'sponsor_user_id')) {
            Schema::table('stockists', function (Blueprint $table) {
                $table->unsignedBigInteger('sponsor_user_id')->nullable()->after('user_id')->index();
            });
        }

        // 2. Add stockist_sponsor_bonus accumulator to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'stockist_sponsor_bonus')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('stockist_sponsor_bonus', 12, 2)->default(0.00)->after('stockist_rebate');
            });
        }

        // 3. Create global_pool_ledgers table
        if (!Schema::hasTable('global_pool_ledgers')) {
            Schema::create('global_pool_ledgers', function (Blueprint $table) {
                $table->id();
                $table->string('period', 20)->index();      // e.g. "Q4-2026"
                $table->decimal('accumulated_amount', 14, 2)->default(0.00);
                $table->decimal('distributed_amount', 14, 2)->default(0.00);
                $table->integer('qualified_count')->default(0);
                $table->string('status', 20)->default('accumulating')->index();
                $table->timestamp('distributed_at')->nullable();
                $table->timestamps();

                $table->unique(['period', 'status']);
            });
        }

        // 4. Add carton packaging columns to products table
        if (Schema::hasTable('products')) {
            if (!Schema::hasColumn('products', 'carton_size')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->unsignedSmallInteger('carton_size')->default(15)->after('quantity');
                });
            }
            if (!Schema::hasColumn('products', 'price_per_carton')) {
                Schema::table('products', function (Blueprint $table) {
                    $table->decimal('price_per_carton', 12, 2)->default(0.00)->after('carton_size');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_pool_ledgers');

        if (Schema::hasTable('stockists') && Schema::hasColumn('stockists', 'sponsor_user_id')) {
            Schema::table('stockists', function (Blueprint $table) {
                $table->dropColumn('sponsor_user_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'stockist_sponsor_bonus')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('stockist_sponsor_bonus');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'carton_size')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['carton_size', 'price_per_carton']);
            });
        }
    }
};
