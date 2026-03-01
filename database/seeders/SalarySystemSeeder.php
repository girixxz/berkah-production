<?php

namespace Database\Seeders;

use App\Models\SalarySystem;
use Illuminate\Database\Seeder;

class SalarySystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systems = [
            ['type_name' => 'monthly_1x'],
            ['type_name' => 'monthly_2x'],
            ['type_name' => 'project_3x'],
        ];

        foreach ($systems as $system) {
            SalarySystem::create($system);
        }
    }
}
