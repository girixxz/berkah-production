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
        Schema::create('order_material_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_id')->constrained('balances')->cascadeOnDelete();
            $table->foreignId('order_report_id')->constrained('order_reports')->cascadeOnDelete();
            $table->date('purchase_date');
            $table->enum('purchase_type', ['first_purchase', 'extra_purchase']);
            $table->string('material_name', 50);
            $table->foreignId('material_supplier_id')->constrained('material_suppliers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->string('proof_img', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_material_reports');
    }
};
