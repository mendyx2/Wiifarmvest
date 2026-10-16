<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stockist;
use App\Models\Sktransaction;


class StockistSktransactionsSeeder extends Seeder
{
    public function run()
    {
        $stockists = Stockist::all();

        foreach ($stockists as $stockist) {
            // Create some sample sktransactions
            Sktransaction::create([
                'stockist_id' => $stockist->id,
                'type' => 'credit',
                'amount' => 50000.00,
                'balance_before' => 0,
                'balance_after' => 50000.00,
                'description' => 'Initial Wallet Funding',
                'reference' => Sktransaction::generateReference(),
            ]);

            Sktransaction::create([
                'stockist_id' => $stockist->id,
                'type' => 'debit',
                'amount' => 15000.00,
                'balance_before' => 50000.00,
                'balance_after' => 35000.00,
                'description' => 'Product Purchase - Order #001',
                'reference' => Sktransaction::generateReference(),
            ]);

            Sktransaction::create([
                'stockist_id' => $stockist->id,
                'type' => 'credit',
                'amount' => 25000.00,
                'balance_before' => 35000.00,
                'balance_after' => 60000.00,
                'description' => 'Wallet Top-up by Admin',
                'reference' => Sktransaction::generateReference(),
                'notes' => 'Monthly allocation',
            ]);
        }
    }
}
