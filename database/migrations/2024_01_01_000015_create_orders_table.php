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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Priority
            $table->enum('priority', ['normal', 'high'])->default('normal');
            
            // Data Order
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('sales_id')->constrained('sales')->onDelete('cascade');
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('deadline')->useCurrent();
            
            // Product & Material Info
            $table->foreignId('product_category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('product_color', 100);
            $table->foreignId('material_category_id')->constrained('material_categories')->onDelete('cascade');
            $table->foreignId('material_texture_id')->constrained('material_textures')->onDelete('cascade');
            $table->text('notes')->nullable();
            
            // Shipping
            $table->enum('shipping_type', ['pickup', 'delivery']);

            // Totals
            $table->integer('total_qty')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            
            // Production Status
            $table->enum('production_status', ['pending', 'wip', 'finished', 'cancelled'])->default('pending');
            $table->timestamp('finished_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
