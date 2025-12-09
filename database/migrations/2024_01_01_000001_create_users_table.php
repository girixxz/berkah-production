<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname', 100);
            $table->string('username', 100)->unique();
            $table->string('phone_number', 100)->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'admin', 'pm', 'karyawan']);

            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('work_date')->nullable();
            $table->string('dress_size')->nullable();
            $table->string('salary_system')->nullable();
            $table->integer('salary_cycle')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
