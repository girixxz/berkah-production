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
        Schema::create('material_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('size_name', 100)->unique();
            $table->decimal('extra_price', 12, 2)->default(0);
            $table->integer('sort_order')->comment('Order for size sorting (smaller first)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_sizes');
    }
};
