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
        // Main Work Orders Table
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('design_variant_id')->constrained('design_variants')->onDelete('cascade');
            $table->text('mockup_img_url')->nullable();
            $table->enum('status', ['pending', 'created'])->default('pending');
            $table->timestamps();
        });

        // Work Order Cuttings
        Schema::create('work_order_cuttings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('cutting_pattern_id')->constrained('cutting_patterns')->onDelete('cascade');
            $table->foreignId('chain_cloth_id')->constrained('chain_cloths')->onDelete('cascade');
            $table->foreignId('rib_size_id')->constrained('rib_sizes')->onDelete('cascade');
            $table->text('custom_size_chart_img_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Work Order Printings
        Schema::create('work_order_printings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('print_ink_id')->constrained('print_inks')->onDelete('cascade');
            $table->foreignId('finishing_id')->constrained('finishings')->onDelete('cascade');
            $table->text('detail_img_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Work Order Printing Placements
        Schema::create('work_order_printing_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->text('detail_img_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Work Order Sewings
        Schema::create('work_order_sewings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('neck_overdeck_id')->constrained('neck_overdecks')->onDelete('cascade');
            $table->foreignId('underarm_overdeck_id')->constrained('underarm_overdecks')->onDelete('cascade');
            $table->foreignId('side_split_id')->constrained('side_splits')->onDelete('cascade');
            $table->foreignId('sewing_label_id')->constrained('sewing_labels')->onDelete('cascade');
            $table->text('detail_img_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Work Order Packings
        Schema::create('work_order_packings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('plastic_packing_id')->constrained('plastic_packings')->onDelete('cascade');
            $table->foreignId('sticker_id')->constrained('stickers')->onDelete('cascade');
            $table->text('hangtag_img_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_packings');
        Schema::dropIfExists('work_order_sewings');
        Schema::dropIfExists('work_order_printing_placements');
        Schema::dropIfExists('work_order_printings');
        Schema::dropIfExists('work_order_cuttings');
        Schema::dropIfExists('work_orders');
    }
};
