<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'customer_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578010', // Gubeng
                'village_id' => '3578010001', // Airlangga
                'address' => 'Jl. Airlangga No. 123',
            ],
            [
                'customer_name' => 'Siti Rahayu',
                'phone' => '082345678901',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578020', // Rungkut
                'village_id' => '3578020001', // Kedung Baruk
                'address' => 'Jl. Rungkut Asri Tengah No. 45',
            ],
            [
                'customer_name' => 'Ahmad Hidayat',
                'phone' => '083456789012',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578030', // Wonokromo
                'village_id' => '3578030001', // Wonokromo
                'address' => 'Jl. Wonokromo No. 78',
            ],
            [
                'customer_name' => 'Dewi Lestari',
                'phone' => '084567890123',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578040', // Tegalsari
                'village_id' => '3578040001', // Dr. Sutomo
                'address' => 'Jl. Basuki Rahmat No. 234',
            ],
            [
                'customer_name' => 'Eko Prasetyo',
                'phone' => '085678901234',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578050', // Genteng
                'village_id' => '3578050001', // Genteng
                'address' => 'Jl. Pemuda No. 56',
            ],
            [
                'customer_name' => 'Fitria Maharani',
                'phone' => '086789012345',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578060', // Mulyorejo
                'village_id' => '3578060001', // Mulyorejo
                'address' => 'Jl. Mulyorejo No. 12',
            ],
            [
                'customer_name' => 'Gunawan Setiawan',
                'phone' => '087890123456',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578070', // Tambaksari
                'village_id' => '3578070001', // Tambaksari
                'address' => 'Jl. Tambaksari No. 89',
            ],
            [
                'customer_name' => 'Hana Puspita',
                'phone' => '088901234567',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578080', // Kenjeran
                'village_id' => '3578080001', // Kenjeran
                'address' => 'Jl. Kenjeran No. 45',
            ],
            [
                'customer_name' => 'Irfan Maulana',
                'phone' => '089012345678',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578090', // Bulak
                'village_id' => '3578090001', // Bulak
                'address' => 'Jl. Bulak No. 67',
            ],
            [
                'customer_name' => 'Jasmine Anggraini',
                'phone' => '081123456789',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578100', // Semampir
                'village_id' => '3578100001', // Ampel
                'address' => 'Jl. Ampel No. 23',
            ],
            [
                'customer_name' => 'Krisna Wijaya',
                'phone' => '082234567890',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578110', // Asemrowo
                'village_id' => '3578110001', // Asemrowo
                'address' => 'Jl. Asemrowo No. 34',
            ],
            [
                'customer_name' => 'Linda Wijayanti',
                'phone' => '083345678901',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578120', // Benowo
                'village_id' => '3578120001', // Benowo
                'address' => 'Jl. Benowo No. 56',
            ],
            [
                'customer_name' => 'Muhammad Rizki',
                'phone' => '084456789012',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578130', // Pabean Cantian
                'village_id' => '3578130001', // Perak Barat
                'address' => 'Jl. Perak Barat No. 78',
            ],
            [
                'customer_name' => 'Nurul Hidayah',
                'phone' => '085567890123',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578140', // Simokerto
                'village_id' => '3578140001', // Simokerto
                'address' => 'Jl. Simokerto No. 90',
            ],
            [
                'customer_name' => 'Oscar Pradipta',
                'phone' => '086678901234',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578150', // Bubutan
                'village_id' => '3578150001', // Bubutan
                'address' => 'Jl. Bubutan No. 12',
            ],
            [
                'customer_name' => 'Putri Ayu',
                'phone' => '087789012345',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578160', // Krembangan
                'village_id' => '3578160001', // Krembangan Selatan
                'address' => 'Jl. Krembangan No. 34',
            ],
            [
                'customer_name' => 'Rendi Saputra',
                'phone' => '088890123456',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578170', // Pakal
                'village_id' => '3578170001', // Pakal
                'address' => 'Jl. Pakal No. 56',
            ],
            [
                'customer_name' => 'Sari Kusuma',
                'phone' => '089901234567',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578180', // Lakarsantri
                'village_id' => '3578180001', // Lakarsantri
                'address' => 'Jl. Lakarsantri No. 78',
            ],
            [
                'customer_name' => 'Taufik Hidayat',
                'phone' => '081212345678',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578190', // Sambikerep
                'village_id' => '3578190001', // Sambikerep
                'address' => 'Jl. Sambikerep No. 90',
            ],
            [
                'customer_name' => 'Umi Kalsum',
                'phone' => '082323456789',
                'province_id' => '35', // Jawa Timur
                'city_id' => '3578', // Surabaya
                'district_id' => '3578200', // Sawahan
                'village_id' => '3578200001', // Sawahan
                'address' => 'Jl. Sawahan No. 11',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
