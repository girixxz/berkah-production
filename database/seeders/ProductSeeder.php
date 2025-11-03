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
            ['product_name' => 'Kaos Oblong'],
            ['product_name' => 'Polo Shirt'],
            ['product_name' => 'Jersey'],
            ['product_name' => 'Jaket'],
            ['product_name' => 'Sweater'],
            ['product_name' => 'Hoodie'],
        ];

        foreach ($productCategories as $category) {
            ProductCategory::create($category);
        }

        // Material Categories
        $materialCategories = [
            ['material_name' => 'Cotton Combed 20s'],
            ['material_name' => 'Cotton Combed 24s'],
            ['material_name' => 'Cotton Combed 30s'],
            ['material_name' => 'Cotton Bamboo'],
            ['material_name' => 'Polyester'],
            ['material_name' => 'Hyget'],
            ['material_name' => 'PE (Polyester)'],
            ['material_name' => 'Lacoste'],
        ];

        foreach ($materialCategories as $material) {
            MaterialCategory::create($material);
        }

        // Material Textures
        $materialTextures = [
            ['texture_name' => 'Soft'],
            ['texture_name' => 'Medium'],
            ['texture_name' => 'Hard'],
            ['texture_name' => 'Smooth'],
            ['texture_name' => 'Rough'],
        ];

        foreach ($materialTextures as $texture) {
            MaterialTexture::create($texture);
        }

        // Material Sleeves
        $materialSleeves = [
            ['sleeve_name' => 'Pendek'],
            ['sleeve_name' => 'Panjang'],
            ['sleeve_name' => 'Raglan'],
            ['sleeve_name' => '3/4'],
        ];

        foreach ($materialSleeves as $sleeve) {
            MaterialSleeve::create($sleeve);
        }

        // Material Sizes
        $materialSizes = [
            ['size_name' => 'S', 'extra_price' => 0],
            ['size_name' => 'M', 'extra_price' => 0],
            ['size_name' => 'L', 'extra_price' => 2000],
            ['size_name' => 'XL', 'extra_price' => 4000],
            ['size_name' => 'XXL', 'extra_price' => 6000],
            ['size_name' => 'XXXL', 'extra_price' => 8000],
            ['size_name' => 'XXXXL', 'extra_price' => 10000],
        ];

        foreach ($materialSizes as $size) {
            MaterialSize::create($size);
        }

        // Services
        $services = [
            ['service_name' => 'Sablon Manual'],
            ['service_name' => 'Sablon Digital'],
            ['service_name' => 'Sablon Rubber'],
            ['service_name' => 'Sablon Plastisol'],
            ['service_name' => 'Bordir Komputer'],
            ['service_name' => 'Bordir Tangan'],
            ['service_name' => 'Printing DTG'],
            ['service_name' => 'Printing Sublim'],
            ['service_name' => 'Polyflex'],
            ['service_name' => 'Heat Transfer'],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
