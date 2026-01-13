<?php

namespace Database\Seeders;

use App\Models\MaterialSupplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MaterialSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'PT. Tekstil Prima Indonesia',
                'notes' => 'Supplier utama untuk kain cotton dan polyester berkualitas tinggi',
                'sort_order' => 1,
            ],
            [
                'supplier_name' => 'CV. Benang Nusantara',
                'notes' => 'Spesialis benang jahit berbagai warna dan ukuran',
                'sort_order' => 2,
            ],
            [
                'supplier_name' => 'PT. Risleting Jaya',
                'notes' => 'Supplier zipper, kancing, dan aksesoris garmen',
                'sort_order' => 3,
            ],
            [
                'supplier_name' => 'Toko Bahan Jaya',
                'notes' => 'Supplier bahan pelengkap seperti kain furing dan interlining',
                'sort_order' => 4,
            ],
            [
                'supplier_name' => 'UD. Label Kreatif',
                'notes' => 'Produsen label woven, printed, dan hang tag custom',
                'sort_order' => 5,
            ],
        ];

        foreach ($suppliers as $supplier) {
            MaterialSupplier::create($supplier);
        }
    }
}
