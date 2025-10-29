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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->string('phone', 20);
            // Location IDs from API (emsifa/api-wilayah-indonesia)
            $table->string('province_id', 10)->nullable();
            $table->string('city_id', 10)->nullable();
            $table->string('district_id', 10)->nullable();
            $table->string('village_id', 20)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
