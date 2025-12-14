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
        // Create default owner user
        User::create([
            'fullname' => 'STGRMFG',
            'username' => 'stgrmfg',
            'password' => bcrypt('berkahmanfaat123#'),
            'role' => 'owner',
        ]);
    }
}
