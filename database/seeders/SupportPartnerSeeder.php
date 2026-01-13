<?php

namespace Database\Seeders;

use App\Models\SupportPartner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupportPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'partner_name' => 'CV. Sablon Express',
                'notes' => 'Jasa sablon manual dan digital printing untuk kaos dan jaket',
                'sort_order' => 1,
            ],
            [
                'partner_name' => 'PT. Bordir Nusantara',
                'notes' => 'Spesialis bordir komputer dengan mesin high speed',
                'sort_order' => 2,
            ],
            [
                'partner_name' => 'Laundry Garmen Pro',
                'notes' => 'Jasa cuci, setrika, dan finishing produk garmen',
                'sort_order' => 3,
            ],
            [
                'partner_name' => 'UD. Kemasan Plastik',
                'notes' => 'Supplier polybag, plastik opp, dan packaging material',
                'sort_order' => 4,
            ],
            [
                'partner_name' => 'Ekspedisi Cepat Kirim',
                'notes' => 'Partner logistik untuk pengiriman dalam dan luar kota',
                'sort_order' => 5,
            ],
            [
                'partner_name' => 'CV. Cutting Service',
                'notes' => 'Jasa cutting bahan menggunakan mesin cutting modern',
                'sort_order' => 6,
            ],
        ];

        foreach ($partners as $partner) {
            SupportPartner::create($partner);
        }
    }
}
