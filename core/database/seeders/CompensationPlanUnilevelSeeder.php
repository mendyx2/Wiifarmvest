<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\UnilevelGeneration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompensationPlanUnilevelSeeder extends Seeder
{
    /**
     * Seed the 15 Unilevel Generations and link them to the 7 registration packages.
     * Page 6 of Proposed Business Compensation Plan.
     */
    public function run(): void
    {
        $generations = [
            ['number' => 1,  'title' => '1st Generation',  'percentage' => 10.0000, 'description' => '10% on 1st Generation repurchase volume'],
            ['number' => 2,  'title' => '2nd Generation',  'percentage' => 7.0000,  'description' => '7% on 2nd Generation repurchase volume'],
            ['number' => 3,  'title' => '3rd Generation',  'percentage' => 5.0000,  'description' => '5% on 3rd Generation repurchase volume'],
            ['number' => 4,  'title' => '4th Generation',  'percentage' => 4.0000,  'description' => '4% on 4th Generation repurchase volume'],
            ['number' => 5,  'title' => '5th Generation',  'percentage' => 4.0000,  'description' => '4% on 5th Generation repurchase volume'],
            ['number' => 6,  'title' => '6th Generation',  'percentage' => 3.0000,  'description' => '3% on 6th Generation repurchase volume'],
            ['number' => 7,  'title' => '7th Generation',  'percentage' => 3.0000,  'description' => '3% on 7th Generation repurchase volume'],
            ['number' => 8,  'title' => '8th Generation',  'percentage' => 3.0000,  'description' => '3% on 8th Generation repurchase volume'],
            ['number' => 9,  'title' => '9th Generation',  'percentage' => 2.0000,  'description' => '2% on 9th Generation repurchase volume'],
            ['number' => 10, 'title' => '10th Generation', 'percentage' => 2.0000,  'description' => '2% on 10th Generation repurchase volume'],
            ['number' => 11, 'title' => '11th Generation', 'percentage' => 2.0000,  'description' => '2% on 11th Generation repurchase volume'],
            ['number' => 12, 'title' => '12th Generation', 'percentage' => 1.0000,  'description' => '1% on 12th Generation repurchase volume'],
            ['number' => 13, 'title' => '13th Generation', 'percentage' => 1.0000,  'description' => '1% on 13th Generation repurchase volume'],
            ['number' => 14, 'title' => '14th Generation', 'percentage' => 1.0000,  'description' => '1% on 14th Generation repurchase volume'],
            ['number' => 15, 'title' => '15th Generation', 'percentage' => 1.0000,  'description' => '1% on 15th Generation repurchase volume'],
        ];

        $genModels = [];
        foreach ($generations as $g) {
            $model = UnilevelGeneration::updateOrCreate(
                ['number' => $g['number']],
                array_merge($g, ['status' => true])
            );
            $genModels[$g['number']] = $model->id;
        }

        // Package generation unlock map per Page 6:
        // Pre-Starter: 1 to 4
        // Starter: 1 to 6
        // Builder: 1 to 8
        // Booster: 1 to 10
        // Executive: 1 to 12
        // Advance: 1 to 13
        // Mega: 1 to 15
        $packageTierLimits = [
            'wiifarm-pre-starter' => 4,
            'wiifarm-starter'     => 6,
            'wiifarm-builder'     => 8,
            'wiifarm-booster'     => 10,
            'wiifarm-executive'   => 12,
            'wiifarm-advance'     => 13,
            'wiifarm-mega'        => 15,
        ];

        foreach ($packageTierLimits as $slug => $maxGen) {
            $project = Project::where('slug', $slug)->first();
            if ($project) {
                $assignGenIds = [];
                for ($i = 1; $i <= $maxGen; $i++) {
                    if (isset($genModels[$i])) {
                        $assignGenIds[] = $genModels[$i];
                    }
                }
                $project->unilevelGenerations()->sync($assignGenIds);
            }
        }
    }
}
