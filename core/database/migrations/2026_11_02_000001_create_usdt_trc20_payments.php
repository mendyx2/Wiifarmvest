<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * USDT (TRC20) deposits to one Wiifarm address (notes Step 122).
 *
 *  - usdt_invoices: the exact amount, address and time window each deposit
 *    was asked to pay. Amounts are whole token units (1 USDT = 1,000,000),
 *    never floats.
 *  - usdt_transfers: every payment the app has seen or been told about, one
 *    row per transaction ID (unique: a payment can be used once, ever).
 *  - the gateway itself, switched off until an admin enters the address.
 *
 * Safe to run twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usdt_invoices')) {
            Schema::create('usdt_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('deposit_id')->unique();
                $table->string('address', 64);
                $table->unsignedBigInteger('units');
                $table->timestamp('expires_at');
                // The payment that settled it (credited, or sent to an admin)
                $table->unsignedBigInteger('transfer_id')->nullable()->unique();
                $table->timestamps();

                $table->index(['units', 'expires_at']);
            });
        }

        if (!Schema::hasTable('usdt_transfers')) {
            Schema::create('usdt_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('txid', 64)->unique();
                $table->string('from_address', 64)->nullable();
                $table->string('to_address', 64);
                $table->unsignedBigInteger('units');
                $table->unsignedBigInteger('block_time_ms');
                // credited | review | unmatched | rejected
                $table->string('status', 16)->index();
                $table->unsignedBigInteger('deposit_id')->nullable()->index();
                // txid (pasted by a member) | scan (found automatically)
                $table->string('source', 8);
                $table->string('note', 255)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('gateways') && !DB::table('gateways')->where('alias', 'UsdtTrc20')->exists()) {
            // The first free code from 540: codes under 1000 are automatic gateways
            $used = DB::table('gateways')->pluck('code')->map(fn ($c) => (int) $c)->all();
            $code = 540;
            while (in_array($code, $used, true)) {
                $code++;
            }

            DB::table('gateways')->insert([
                'form_id'              => 0,
                'code'                 => $code,
                'name'                 => 'USDT (TRC20)',
                'alias'                => 'UsdtTrc20',
                'image'                => null,
                'status'               => 0,
                'crypto'               => 1,
                'description'          => 'Pay in USDT on the Tron network (TRC20) to the Wiifarm address.',
                'gateway_parameters'   => json_encode([
                    'wallet_address'   => ['title' => 'Wiifarm USDT (TRC20) address', 'global' => true, 'value' => ''],
                    'trongrid_api_key' => ['title' => 'TronGrid API key', 'global' => true, 'value' => ''],
                ]),
                'supported_currencies' => json_encode(['USDT' => '₮']),
                'extra'                => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Payment records are kept: they are the audit trail of real money.
        DB::table('gateways')->where('alias', 'UsdtTrc20')->update(['status' => 0]);
    }
};
