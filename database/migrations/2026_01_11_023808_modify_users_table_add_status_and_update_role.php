<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Copy existing user data to user_profiles
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::table('user_profiles')->insert([
                'user_id' => $user->id,
                'fullname' => $user->fullname ?? 'Unknown',
                'phone_number' => $user->phone_number ?? null,
                'gender' => $user->gender ? strtolower($user->gender) : null,
                'birth_date' => $user->birth_date ?? null,
                'work_date' => $user->work_date ?? null,
                'dress_size' => $user->dress_size ?? null,
                'address' => $user->address ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Step 2: Modify role enum FIRST to add 'finance' and 'employee' (keep 'karyawan' temporarily)
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'admin', 'finance', 'pm', 'karyawan', 'employee') NOT NULL");

        // Step 3: Update 'karyawan' role to 'employee'
        DB::table('users')->where('role', 'karyawan')->update(['role' => 'employee']);

        // Step 4: Add status column
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive'])->default('active')->after('role');
        });

        // Step 5: Modify role enum again to remove 'karyawan'
        DB::statement("ALTER TABLE users MODIFY role ENUM('owner', 'admin', 'finance', 'pm', 'employee') NOT NULL");

        // Step 6: Drop old columns from users table
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fullname')) {
                $table->dropColumn('fullname');
            }
            if (Schema::hasColumn('users', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
            if (Schema::hasColumn('users', 'gender')) {
                $table->dropColumn('gender');
            }
            if (Schema::hasColumn('users', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
            if (Schema::hasColumn('users', 'work_date')) {
                $table->dropColumn('work_date');
            }
            if (Schema::hasColumn('users', 'dress_size')) {
                $table->dropColumn('dress_size');
            }
            if (Schema::hasColumn('users', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('users', 'salary_system')) {
                $table->dropColumn('salary_system');
            }
            if (Schema::hasColumn('users', 'salary_cycle')) {
                $table->dropColumn('salary_cycle');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback is too complex and risky - use database backup instead
        throw new \Exception('Rollback not supported. Restore from database backup instead.');
    }
};
