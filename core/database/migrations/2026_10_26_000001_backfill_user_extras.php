<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give every member a user_extras row (BV and left/right counters).
 * Sign-up and Add Distributor never created one, and the admin user page
 * reads $user->userExtra->bv_left without a null check, so it failed for
 * those members. New members get theirs from User::booted(). Safe to run
 * twice: it only adds rows that are missing. See notes Step 88.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_extras') || !Schema::hasTable('users')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->whereNotIn('id', DB::table('user_extras')->select('user_id'))
            ->orderBy('id')
            ->select('id')
            ->chunkById(1000, function ($users) use ($now) {
                DB::table('user_extras')->insert($users->map(fn ($u) => [
                    'user_id'    => $u->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        // Nothing to undo: the rows hold zeros until the plan writes to them.
    }
};
