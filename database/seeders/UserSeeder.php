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
        // REAL USERS
        $users = [
            ['fullname' => 'STGRMFG', 'username' => 'stgrmfg', 'password' => 'berkahmanfaat123#', 'role' => 'owner'],
            ['fullname' => 'FERRY ARDIYANTO', 'username' => 'stgr.ferry', 'password' => '2015176', 'role' => 'employee'],
            ['fullname' => 'YUSLAN RIMBANI', 'username' => 'stgr.yuslan', 'password' => '2015028', 'role' => 'employee'],
            ['fullname' => 'JOKO WISNU SAPUTRO', 'username' => 'stgr.wisnu', 'password' => '2019059', 'role' => 'employee'],
            ['fullname' => 'TONI GARDIYANTO', 'username' => 'stgr.toni', 'password' => '2018183', 'role' => 'employee'],
            ['fullname' => 'ANANG AWAN GITA', 'username' => 'stgr.anang', 'password' => '2020057', 'role' => 'employee'],
            ['fullname' => 'SARJIMAN', 'username' => 'stgr.sarjimar', 'password' => '20220710', 'role' => 'employee'],
            ['fullname' => 'SUDARUN', 'username' => 'stgr.sudarun', 'password' => '20221812', 'role' => 'employee'],
            ['fullname' => 'AHMAD MIFTAHUL ULUM', 'username' => 'stgr.ulum', 'password' => '2023119', 'role' => 'employee'],
            ['fullname' => 'HENDRA YULIANTO', 'username' => 'stgr.hendra', 'password' => '2022067', 'role' => 'employee'],
            ['fullname' => 'MUHAMMAD ARIF SAPUTRO', 'username' => 'stgr.arif', 'password' => '2021247', 'role' => 'employee'],
            ['fullname' => 'TRIYONO', 'username' => 'stgr.yono', 'password' => '2025025', 'role' => 'employee'],
            ['fullname' => 'NASRON', 'username' => 'stgr.nasron', 'password' => '20250311', 'role' => 'employee'],
            ['fullname' => 'RADITA AFADILA', 'username' => 'stgr.radita', 'password' => '20252511', 'role' => 'employee'],
            ['fullname' => 'ARDIYANSAH', 'username' => 'stgr.ardi', 'password' => '2025093', 'role' => 'employee'],
            ['fullname' => 'DELLA THUNY AFRIANI', 'username' => 'stgr.della', 'password' => '2025164', 'role' => 'owner'],
            ['fullname' => 'ULFA IRZA LABIBA', 'username' => 'stgr.ulfa', 'password' => '20252712', 'role' => 'admin'],
            ['fullname' => 'WIKAN LAKSITA NARISWARI', 'username' => 'stgr.wikan', 'password' => '2025205', 'role' => 'admin'],
            ['fullname' => 'AHMAD DONI', 'username' => 'stgr.doni', 'password' => '2022201', 'role' => 'admin'],
            ['fullname' => 'DIMAS ALDI KURNIAWAN', 'username' => 'stgr.aldi', 'password' => '2024093', 'role' => 'employee'],
            ['fullname' => 'Jahitin By STGR', 'username' => 'stgr.jahitin', 'password' => 'sedino200', 'role' => 'employee'],
        ];

        // TESTING USERS
        // $users = [
        //     ['fullname' => 'Super User', 'username' => 'superuser', 'password' => 'berkahjagonya1#', 'role' => 'owner'],
        // ];

        foreach ($users as $userData) {
            $user = User::create([
                'username' => $userData['username'],
                'password' => bcrypt($userData['password']),
                'role' => $userData['role'],
                'status' => 'active',
            ]);

            // Create profile for user
            $user->profile()->create([
                'fullname' => $userData['fullname'],
                'phone_number' => null,
                'gender' => 'male',
                'birth_date' => null,
                'work_date' => null,
                'dress_size' => null,
                'address' => null,
            ]);
        }
    }
}
