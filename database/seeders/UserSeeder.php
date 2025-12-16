<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['fullname' => 'STGRMFG', 'username' => 'stgrmfg', 'password' => 'berkahmanfaat123#', 'role' => 'owner'],
            ['fullname' => 'FERRY ARDIYANTO', 'username' => 'stgr.ferry', 'password' => '2015176', 'role' => 'karyawan'],
            ['fullname' => 'YUSLAN RIMBANI', 'username' => 'stgr.yuslan', 'password' => '2015028', 'role' => 'karyawan'],
            ['fullname' => 'JOKO WISNU SAPUTRO', 'username' => 'stgr.wisnu', 'password' => '2019059', 'role' => 'karyawan'],
            ['fullname' => 'TONI GARDIYANTO', 'username' => 'stgr.toni', 'password' => '2018183', 'role' => 'karyawan'],
            ['fullname' => 'ANANG AWAN GITA', 'username' => 'stgr.anang', 'password' => '2020057', 'role' => 'karyawan'],
            ['fullname' => 'SARJIMAN', 'username' => 'stgr.sarjimar', 'password' => '20220710', 'role' => 'karyawan'],
            ['fullname' => 'SUDARUN', 'username' => 'stgr.sudarun', 'password' => '20221812', 'role' => 'karyawan'],
            ['fullname' => 'AHMAD MIFTAHUL ULUM', 'username' => 'stgr.ulum', 'password' => '2023119', 'role' => 'karyawan'],
            ['fullname' => 'HENDRA YULIANTO', 'username' => 'stgr.hendra', 'password' => '2022067', 'role' => 'karyawan'],
            ['fullname' => 'MUHAMMAD ARIF SAPUTRO', 'username' => 'stgr.arif', 'password' => '2021247', 'role' => 'karyawan'],
            ['fullname' => 'TRIYONO', 'username' => 'stgr.yono', 'password' => '2025025', 'role' => 'karyawan'],
            ['fullname' => 'NASRON', 'username' => 'stgr.nasron', 'password' => '20250311', 'role' => 'karyawan'],
            ['fullname' => 'RADITA AFADILA', 'username' => 'stgr.radita', 'password' => '20252511', 'role' => 'karyawan'],
            ['fullname' => 'ARDIYANSAH', 'username' => 'stgr.ardi', 'password' => '2025093', 'role' => 'karyawan'],
            ['fullname' => 'DELLA THUNY AFRIANI', 'username' => 'stgr.della', 'password' => '2025164', 'role' => 'owner'],
            ['fullname' => 'ULFA IRZA LABIBA', 'username' => 'stgr.ulfa', 'password' => '20252712', 'role' => 'admin'],
            ['fullname' => 'WIKAN LAKSITA NARISWARI', 'username' => 'stgr.wikan', 'password' => '2025205', 'role' => 'admin'],
            ['fullname' => 'AHMAD DONI', 'username' => 'stgr.doni', 'password' => '2022201', 'role' => 'admin'],
            ['fullname' => 'DIMAS ALDI KURNIAWAN', 'username' => 'stgr.aldi', 'password' => '2024093', 'role' => 'karyawan'],
            ['fullname' => 'Jahitin By STGR', 'username' => 'stgr.jahitin', 'password' => 'sedino200', 'role' => 'karyawan'],
        ];

        foreach ($users as $userData) {
            User::create([
                'fullname' => $userData['fullname'],
                'username' => $userData['username'],
                'password' => bcrypt($userData['password']),
                'role' => $userData['role'],
            ]);
        }
    }
}
