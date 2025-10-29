<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SaleSeeder::class,
            // LocationSeeder::class, // No longer needed - using API
            // CustomerSeeder::class, // Deleted - customers will be added manually via UI
            ProductSeeder::class,
            // OrderSeeder::class,
            ProductionStageSeeder::class,
        ]);
    }
}
