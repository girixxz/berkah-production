<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear all uploaded images from storage
        // WARNING: DISABLED to prevent data loss in production
        // Only enable in local development when needed
        // $this->clearStorageFiles();

        $this->call([
            UserSeeder::class,
            SalarySystemSeeder::class,
            SaleSeeder::class,
            ProductSeeder::class,
            ProductionStageSeeder::class,
            MaterialSupplierSeeder::class,
            SupportPartnerSeeder::class,
            FixCostListSeeder::class,
            WorkOrderDataSeeder::class,
            CustomerSeeder::class,
            // OrderSeeder::class,
            WorkOrderDataSeeder::class
        ]);
    }

    /**
     * Clear all uploaded files from storage directories
     */
    private function clearStorageFiles(): void
    {
        $directories = [
            'private/orders',           // Order images
            'private/payments',         // Payment images
            'private/work-orders',      // Work Order images (mockup, printing, sewing, etc)
            // Add more directories as needed
        ];

        foreach ($directories as $directory) {
            // Clear from storage/app
            $path = storage_path('app/' . $directory);
            
            $this->command->info("Checking path: {$path}");
            
            if (File::exists($path)) {
                $this->command->info("Path exists, deleting...");
                // Delete entire directory and recreate it (to remove all subfolders)
                File::deleteDirectory($path);
                File::makeDirectory($path, 0755, true);
                $this->command->info("✓ Cleared storage: {$directory}");
            } else {
                $this->command->warn("Path not found: {$path}");
            }
        }
    }
}
