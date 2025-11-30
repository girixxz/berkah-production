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
            ProductSeeder::class,
            ProductionStageSeeder::class,
            WorkOrderDataSeeder::class, // Work Order Master Data
            CustomerSeeder::class,
            // OrderSeeder::class,
            WorkOrderDataSeeder::class
        ]);
    }
}
