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
        Schema::create('fix_cost_lists', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['fix_cost_1', 'fix_cost_2', 'screening'])->notNullable();
            $table->string('list_name', 100);
            $table->integer('sort_order');
            $table->timestamps();

            // Unique composite index on category and list_name
            $table->unique(['category', 'list_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fix_cost_lists');
    }
};
