<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompensationPlanProjectsSeeder extends Seeder
{
    /**
     * Run the database seeds for the 7 Wiifarm Proposed Compensation Plan packages.
     *
     * 1 PV = ₦600
     * Direct Referral = 25% of PV
     * Step Matching = 50 PV
     */
    public function run(): void
    {
        $packages = [
            [
                'title'             => 'Wiifarm-Pre Starter',
                'slug'              => 'wiifarm-pre-starter',
                'amount'            => 15000.00,
                'pv'                => 15.00,
                'direct_commission' => 2250.00,  // 25% of 15 PV * 600
                'pairing_per_day'   => 900.00,   // 3% matching per 50 PV step
                'max_pairing_slots' => 5,        // Daily cap: ₦4,500
                'cash_back'         => 0.00,
                'sort_order'        => 1,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Starter',
                'slug'              => 'wiifarm-starter',
                'amount'            => 30000.00,
                'pv'                => 30.00,
                'direct_commission' => 4500.00,  // 25% of 30 PV * 600
                'pairing_per_day'   => 1500.00,  // 5% matching per 50 PV step
                'max_pairing_slots' => 7,        // Daily cap: ₦10,500
                'cash_back'         => 0.00,
                'sort_order'        => 2,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Builder',
                'slug'              => 'wiifarm-builder',
                'amount'            => 60000.00,
                'pv'                => 60.00,
                'direct_commission' => 9000.00,  // 25% of 60 PV * 600
                'pairing_per_day'   => 2100.00,  // 7% matching per 50 PV step
                'max_pairing_slots' => 10,       // Daily cap: ₦21,000
                'cash_back'         => 0.00,
                'sort_order'        => 3,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Booster',
                'slug'              => 'wiifarm-booster',
                'amount'            => 105000.00,
                'pv'                => 105.00,
                'direct_commission' => 15750.00, // 25% of 105 PV * 600
                'pairing_per_day'   => 2400.00,  // 8% matching per 50 PV step
                'max_pairing_slots' => 30,       // Daily cap: ₦72,000
                'cash_back'         => 0.00,
                'sort_order'        => 4,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Executive',
                'slug'              => 'wiifarm-executive',
                'amount'            => 150000.00,
                'pv'                => 150.00,
                'direct_commission' => 22500.00, // 25% of 150 PV * 600
                'pairing_per_day'   => 2700.00,  // 9% matching per 50 PV step
                'max_pairing_slots' => 60,       // Daily cap: ₦162,000
                'cash_back'         => 0.00,
                'sort_order'        => 5,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Advance',
                'slug'              => 'wiifarm-advance',
                'amount'            => 255000.00,
                'pv'                => 255.00,
                'direct_commission' => 38250.00, // 25% of 255 PV * 600
                'pairing_per_day'   => 3000.00,  // 10% matching per 50 PV step
                'max_pairing_slots' => 90,       // Daily cap: ₦270,000
                'cash_back'         => 0.00,
                'sort_order'        => 6,
                'status'            => true,
            ],
            [
                'title'             => 'Wiifarm-Mega',
                'slug'              => 'wiifarm-mega',
                'amount'            => 450000.00,
                'pv'                => 450.00,
                'direct_commission' => 67500.00, // 25% of 450 PV * 600
                'pairing_per_day'   => 3600.00,  // 12% matching per 50 PV step
                'max_pairing_slots' => 120,      // Daily cap: ₦432,000
                'cash_back'         => 0.00,
                'sort_order'        => 7,
                'status'            => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Project::updateOrCreate(
                ['slug' => $pkg['slug']],
                $pkg
            );
        }
    }
}
