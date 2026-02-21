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
        Schema::create('order_reports', function (Blueprint $table) {
            $table->id();
            
            // Period
            $table->date('period_start');
            $table->date('period_end');
            
            // Foreign Keys
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            
            // Product Type
            $table->enum('product_type', ['t-shirt', 'makloon', 'hoodie_polo_jersey', 'pants']);
            
            // Lock Status

            
            // Note
            $table->text('note')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reports');
    }
};
