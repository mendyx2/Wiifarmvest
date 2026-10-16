<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('leadership_settings')) {
            Schema::create('leadership_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('required_pv', 12, 2)->default(1000.00);
                $table->decimal('percentage', 5, 2)->default(4.00);
                $table->integer('max_levels')->default(5);
                $table->boolean('is_active')->default(true);
                $table->timestamp('enabled_at')->nullable();
                $table->timestamps();
            });

            DB::table('leadership_settings')->insert([
                'required_pv' => 1000.00,
                'percentage'  => 4.00,
                'max_levels'  => 5,
                'is_active'   => true,
                'enabled_at'  => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if (!Schema::hasTable('leadership_leaders')) {
            Schema::create('leadership_leaders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->decimal('pv_left', 12, 2)->default(0.00);
                $table->decimal('pv_right', 12, 2)->default(0.00);
                $table->decimal('required_pv', 12, 2)->default(1000.00);
                $table->boolean('status')->default(true)->index();
                $table->timestamp('qualified_at')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('leadership_bonus_logs')) {
            Schema::create('leadership_bonus_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leader_id')->index();
                $table->unsignedBigInteger('source_user_id')->index();
                $table->unsignedBigInteger('matching_bonus_log_id')->nullable()->index();
                $table->integer('level')->default(1);
                $table->string('leg', 10)->nullable();
                $table->decimal('matching_amount', 12, 2)->default(0.00);
                $table->decimal('percentage', 5, 2)->default(4.00);
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->string('trx', 40)->nullable()->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('matching_bonus_logs') && !Schema::hasColumn('matching_bonus_logs', 'leadership_processed_at')) {
            Schema::table('matching_bonus_logs', function (Blueprint $table) {
                $table->timestamp('leadership_processed_at')->nullable()->after('processed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leadership_bonus_logs');
        Schema::dropIfExists('leadership_leaders');
        Schema::dropIfExists('leadership_settings');

        if (Schema::hasTable('matching_bonus_logs') && Schema::hasColumn('matching_bonus_logs', 'leadership_processed_at')) {
            Schema::table('matching_bonus_logs', function (Blueprint $table) {
                $table->dropColumn('leadership_processed_at');
            });
        }
    }
};
