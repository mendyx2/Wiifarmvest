<?php

namespace Database\Seeders;

use App\Models\RepurchaseAward;
use Illuminate\Database\Seeder;

class CompensationPlanRepurchaseAwardsSeeder extends Seeder
{
    /**
     * Seed the 10 Repurchase (Unilevel) Awards from the Proposed Business Compensation Plan (Page 7).
     *
     * Cumulative Repurchase PV thresholds and cash/item awards:
     * 1.  750 PV   → ₦50,000 (Phone or cash)
     * 2.  1,500 PV → ₦100,000 (Laptop or cash)
     * 3.  3,000 PV → ₦200,000 (Refrigerator / Generator or cash)
     * 4.  6,000 PV → ₦400,000 (International Trip or cash)
     * 5.  10,000 PV → ₦700,000 (Mini Car or cash)
     * 6.  15,000 PV → ₦1,200,000 (Car Award or cash)
     * 7.  20,000 PV → ₦1,800,000 (SUV Award or cash)
     * 8.  25,000 PV → ₦2,500,000 (Prime SUV Award or cash)
     * 9.  35,000 PV → ₦3,500,000 (House Fund or cash)
     * 10. 45,000 PV → ₦5,000,000 (Estate Fund or cash)
     */
    public function run(): void
    {
        $awards = [
            [
                'title'       => 'Phone Award',
                'required_pv' => 750.00,
                'amount'      => 50000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Laptop Award',
                'required_pv' => 1500.00,
                'amount'      => 100000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Generator / Fridge Award',
                'required_pv' => 3000.00,
                'amount'      => 200000.00,
                'status'      => true,
            ],
            [
                'title'       => 'International Trip Award',
                'required_pv' => 6000.00,
                'amount'      => 400000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Mini Car Award',
                'required_pv' => 10000.00,
                'amount'      => 700000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Car Award',
                'required_pv' => 15000.00,
                'amount'      => 1200000.00,
                'status'      => true,
            ],
            [
                'title'       => 'SUV Award',
                'required_pv' => 20000.00,
                'amount'      => 1800000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Prime SUV Award',
                'required_pv' => 25000.00,
                'amount'      => 2500000.00,
                'status'      => true,
            ],
            [
                'title'       => 'House Fund Award',
                'required_pv' => 35000.00,
                'amount'      => 3500000.00,
                'status'      => true,
            ],
            [
                'title'       => 'Estate Fund Award',
                'required_pv' => 45000.00,
                'amount'      => 5000000.00,
                'status'      => true,
            ],
        ];

        foreach ($awards as $item) {
            RepurchaseAward::updateOrCreate(
                ['required_pv' => $item['required_pv']],
                $item
            );
        }
    }
}
