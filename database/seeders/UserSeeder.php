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
        // Create one user for each role
        User::factory()->create([
            'fullname' => 'Owner User',
            'username' => 'owner',
            'role' => 'owner',
        ]);

        User::factory()->create([
            'fullname' => 'Admin User',
            'username' => 'admin',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'fullname' => 'PM User',
            'username' => 'pm',
            'role' => 'pm',
        ]);

        User::factory()->create([
            'fullname' => 'Karyawan User',
            'username' => 'karyawan',
            'role' => 'karyawan',
        ]);

        // Create additional random users
        // User::factory(10)->create();
    }
}
