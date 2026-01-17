<?php

namespace Database\Seeders;

use App\Models\FixCostList;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixCostListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fixCosts = [
            // Fix Cost 1
            [
                'category' => 'fix_cost_1',
                'list_name' => 'Electricity',
                'sort_order' => 1,
            ],
            [
                'category' => 'fix_cost_1',
                'list_name' => 'Water',
                'sort_order' => 2,
            ],
            [
                'category' => 'fix_cost_1',
                'list_name' => 'Internet',
                'sort_order' => 3,
            ],
            [
                'category' => 'fix_cost_1',
                'list_name' => 'Building Rent',
                'sort_order' => 4,
            ],
            [
                'category' => 'fix_cost_1',
                'list_name' => 'Office Supplies',
                'sort_order' => 5,
            ],

            // Fix Cost 2
            [
                'category' => 'fix_cost_2',
                'list_name' => 'Equipment Maintenance',
                'sort_order' => 6,
            ],
            [
                'category' => 'fix_cost_2',
                'list_name' => 'Transportation',
                'sort_order' => 7,
            ],
            [
                'category' => 'fix_cost_2',
                'list_name' => 'Marketing & Advertising',
                'sort_order' => 8,
            ],
            [
                'category' => 'fix_cost_2',
                'list_name' => 'Insurance',
                'sort_order' => 9,
            ],
            [
                'category' => 'fix_cost_2',
                'list_name' => 'Legal & Administration',
                'sort_order' => 10,
            ],

            // Screening
            [
                'category' => 'screening',
                'list_name' => 'Screen Making',
                'sort_order' => 11,
            ],
            [
                'category' => 'screening',
                'list_name' => 'Screen Coating',
                'sort_order' => 12,
            ],
            [
                'category' => 'screening',
                'list_name' => 'Screen Exposure',
                'sort_order' => 13,
            ],
            [
                'category' => 'screening',
                'list_name' => 'Screen Washing',
                'sort_order' => 14,
            ],
            [
                'category' => 'screening',
                'list_name' => 'Screen Reclaiming',
                'sort_order' => 15,
            ],
        ];

        foreach ($fixCosts as $fixCost) {
            FixCostList::create($fixCost);
        }
    }
}
