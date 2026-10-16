<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Seeder;

class CompensationPlanAwardsSeeder extends Seeder
{
    /**
     * Seed the 13 Proposed Compensation Plan Binary Recognition Awards.
     * Pages 4 & 5 of Proposed Plan.
     */
    public function run(): void
    {
        $awards = [
            [
                'id'                   => 1,
                'name'                 => 'Wiifarm-Emerald',
                'description'          => '₦80,000.00 cash gift only (Must be on Wiifarm Builder package)',
                'required_total_pv'    => 3000.00,
                'required_left_pv'     => 1500.00,
                'required_right_pv'    => 1500.00,
                'prerequisite_award_id'=> null,
                'payment_amount'       => 80000.00,
                'sort_order'           => 1,
                'status'               => 1,
            ],
            [
                'id'                   => 2,
                'name'                 => 'Wiifarm-Silver',
                'description'          => '₦150,000.00 cash gift only (Must be on Wiifarm Booster package)',
                'required_total_pv'    => 6000.00,
                'required_left_pv'     => 3000.00,
                'required_right_pv'    => 3000.00,
                'prerequisite_award_id'=> null,
                'payment_amount'       => 150000.00,
                'sort_order'           => 2,
                'status'               => 1,
            ],
            [
                'id'                   => 3,
                'name'                 => 'Wiifarm-Jade',
                'description'          => '₦300,000.00 cash gift only (Must be on Wiifarm Executive package)',
                'required_total_pv'    => 12000.00,
                'required_left_pv'     => 6000.00,
                'required_right_pv'    => 6000.00,
                'prerequisite_award_id'=> null,
                'payment_amount'       => 300000.00,
                'sort_order'           => 3,
                'status'               => 1,
            ],
            [
                'id'                   => 4,
                'name'                 => 'Wiifarm-Gold',
                'description'          => '₦550,000.00 cash or Laptop (Mega package + Jade on Left & Right)',
                'required_total_pv'    => 25000.00,
                'required_left_pv'     => 12500.00,
                'required_right_pv'    => 12500.00,
                'prerequisite_award_id'=> 3, // Jade
                'payment_amount'       => 550000.00,
                'sort_order'           => 4,
                'status'               => 1,
            ],
            [
                'id'                   => 5,
                'name'                 => 'Wiifarm-Diamond',
                'description'          => '₦1,200,000.00 cash or Smart TV (Mega package + Gold on Left & Right)',
                'required_total_pv'    => 50000.00,
                'required_left_pv'     => 25000.00,
                'required_right_pv'    => 25000.00,
                'prerequisite_award_id'=> 4, // Gold
                'payment_amount'       => 1200000.00,
                'sort_order'           => 5,
                'status'               => 1,
            ],
            [
                'id'                   => 6,
                'name'                 => 'Wiifarm-Executive Diamond',
                'description'          => '₦1,500,000.00 or Solar Freezer and Washing machine (Mega + Diamond L&R)',
                'required_total_pv'    => 70000.00,
                'required_left_pv'     => 35000.00,
                'required_right_pv'    => 35000.00,
                'prerequisite_award_id'=> 5, // Diamond
                'payment_amount'       => 1500000.00,
                'sort_order'           => 6,
                'status'               => 1,
            ],
            [
                'id'                   => 7,
                'name'                 => 'Wiifarm-Platinum',
                'description'          => '₦4,000,000.00 or complete Solar system (Mega + Exec Diamond L&R)',
                'required_total_pv'    => 160000.00,
                'required_left_pv'     => 80000.00,
                'required_right_pv'    => 80000.00,
                'prerequisite_award_id'=> 6, // Exec Diamond
                'payment_amount'       => 4000000.00,
                'sort_order'           => 7,
                'status'               => 1,
            ],
            [
                'id'                   => 8,
                'name'                 => 'Wiifarm-Premium Platinum',
                'description'          => '₦10,000,000.00 cash or Small Car (Mega + Platinum L&R)',
                'required_total_pv'    => 300000.00,
                'required_left_pv'     => 150000.00,
                'required_right_pv'    => 150000.00,
                'prerequisite_award_id'=> 7, // Platinum
                'payment_amount'       => 10000000.00,
                'sort_order'           => 8,
                'status'               => 1,
            ],
            [
                'id'                   => 9,
                'name'                 => 'Wiifarm-Ambassador',
                'description'          => '₦16,000,000.00 cash or International Trip (Mega + Premium Platinum L&R)',
                'required_total_pv'    => 600000.00,
                'required_left_pv'     => 300000.00,
                'required_right_pv'    => 300000.00,
                'prerequisite_award_id'=> 8, // Premium Platinum
                'payment_amount'       => 16000000.00,
                'sort_order'           => 9,
                'status'               => 1,
            ],
            [
                'id'                   => 10,
                'name'                 => 'Wiifarm-Executive Ambassador',
                'description'          => '₦25,000,000.00 cash or Big Car (Mega + Ambassador L&R)',
                'required_total_pv'    => 900000.00,
                'required_left_pv'     => 450000.00,
                'required_right_pv'    => 450000.00,
                'prerequisite_award_id'=> 9, // Ambassador
                'payment_amount'       => 25000000.00,
                'sort_order'           => 10,
                'status'               => 1,
            ],
            [
                'id'                   => 11,
                'name'                 => 'Wiifarm-Sapphire',
                'description'          => '₦40,000,000.00 cash or Land (Mega + Executive Ambassador L&R)',
                'required_total_pv'    => 1600000.00,
                'required_left_pv'     => 800000.00,
                'required_right_pv'    => 800000.00,
                'prerequisite_award_id'=> 10, // Executive Ambassador
                'payment_amount'       => 40000000.00,
                'sort_order'           => 11,
                'status'               => 1,
            ],
            [
                'id'                   => 12,
                'name'                 => 'Wiifarm-Executive Sapphire',
                'description'          => '₦100,000,000.00 cash or small bungalow (Mega + Sapphire L&R)',
                'required_total_pv'    => 5000000.00,
                'required_left_pv'     => 2500000.00,
                'required_right_pv'    => 2500000.00,
                'prerequisite_award_id'=> 11, // Sapphire
                'payment_amount'       => 100000000.00,
                'sort_order'           => 12,
                'status'               => 1,
            ],
            [
                'id'                   => 13,
                'name'                 => 'Wiifarm-Global Mega',
                'description'          => '₦180,000,000.00 cash or duplex (Mega + Executive Sapphire L&R)',
                'required_total_pv'    => 10000000.00,
                'required_left_pv'     => 5000000.00,
                'required_right_pv'    => 5000000.00,
                'prerequisite_award_id'=> 12, // Executive Sapphire
                'payment_amount'       => 180000000.00,
                'sort_order'           => 13,
                'status'               => 1,
            ],
        ];

        foreach ($awards as $item) {
            Award::updateOrCreate(
                ['id' => $item['id']],
                $item
            );
        }
    }
}
