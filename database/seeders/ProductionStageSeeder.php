<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionStage;

class ProductionStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            ['stage_name' => 'PO'],
            ['stage_name' => 'Material'],
            ['stage_name' => 'Cutting'],
            ['stage_name' => 'Screening'],
            ['stage_name' => 'Printing'],
            ['stage_name' => 'Embroid'],
            ['stage_name' => 'Sewing'],
            ['stage_name' => 'Packing'],
        ];

        foreach ($stages as $stage) {
            ProductionStage::create($stage);
        }
    }
}
