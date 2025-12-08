<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\MaterialCategory;
use App\Models\MaterialTexture;
use App\Models\MaterialSleeve;
use App\Models\MaterialSize;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Product Categories
        $productCategories = [
            ['product_name' => 'Kaos Oblong', 'sort_order' => 1],
            ['product_name' => 'Polo Shirt', 'sort_order' => 2],
            ['product_name' => 'Jersey', 'sort_order' => 3],
            ['product_name' => 'Jaket', 'sort_order' => 4],
            ['product_name' => 'Sweater', 'sort_order' => 5],
            ['product_name' => 'Hoodie', 'sort_order' => 6],
        ];

        foreach ($productCategories as $category) {
            ProductCategory::create($category);
        }

        // Material Categories
        $materialCategories = [
            ['material_name' => 'Cotton Combed 20s', 'sort_order' => 1],
            ['material_name' => 'Cotton Combed 24s', 'sort_order' => 2],
            ['material_name' => 'Cotton Combed 30s', 'sort_order' => 3],
            ['material_name' => 'Cotton Bamboo', 'sort_order' => 4],
            ['material_name' => 'Polyester', 'sort_order' => 5],
            ['material_name' => 'Hyget', 'sort_order' => 6],
            ['material_name' => 'PE (Polyester)', 'sort_order' => 7],
            ['material_name' => 'Lacoste', 'sort_order' => 8],
        ];

        foreach ($materialCategories as $material) {
            MaterialCategory::create($material);
        }

        // Material Textures
        $materialTextures = [
            ['texture_name' => 'Soft', 'sort_order' => 1],
            ['texture_name' => 'Medium', 'sort_order' => 2],
            ['texture_name' => 'Hard', 'sort_order' => 3],
            ['texture_name' => 'Smooth', 'sort_order' => 4],
            ['texture_name' => 'Rough', 'sort_order' => 5],
        ];

        foreach ($materialTextures as $texture) {
            MaterialTexture::create($texture);
        }

        // Material Sleeves
        $materialSleeves = [
            ['sleeve_name' => 'Pendek', 'sort_order' => 1],
            ['sleeve_name' => 'Panjang', 'sort_order' => 2],
            ['sleeve_name' => 'Raglan', 'sort_order' => 3],
            ['sleeve_name' => '3/4', 'sort_order' => 4],
        ];

        foreach ($materialSleeves as $sleeve) {
            MaterialSleeve::create($sleeve);
        }

        // Material Sizes
        $materialSizes = [
            ['size_name' => 'S', 'extra_price' => 0, 'sort_order' => 1],
            ['size_name' => 'M', 'extra_price' => 0, 'sort_order' => 2],
            ['size_name' => 'L', 'extra_price' => 2000, 'sort_order' => 3],
            ['size_name' => 'XL', 'extra_price' => 4000, 'sort_order' => 4],
            ['size_name' => 'XXL', 'extra_price' => 6000, 'sort_order' => 5],
            ['size_name' => 'XXXL', 'extra_price' => 8000, 'sort_order' => 6],
            ['size_name' => 'XXXXL', 'extra_price' => 10000, 'sort_order' => 7],
        ];

        foreach ($materialSizes as $size) {
            MaterialSize::create($size);
        }

        // Services
        $services = [
            ['service_name' => 'Sablon Manual', 'sort_order' => 1],
            ['service_name' => 'Sablon Digital', 'sort_order' => 2],
            ['service_name' => 'Sablon Rubber', 'sort_order' => 3],
            ['service_name' => 'Sablon Plastisol', 'sort_order' => 4],
            ['service_name' => 'Bordir Komputer', 'sort_order' => 5],
            ['service_name' => 'Bordir Tangan', 'sort_order' => 6],
            ['service_name' => 'Printing DTG', 'sort_order' => 7],
            ['service_name' => 'Printing Sublim', 'sort_order' => 8],
            ['service_name' => 'Polyflex', 'sort_order' => 9],
            ['service_name' => 'Heat Transfer', 'sort_order' => 10],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
