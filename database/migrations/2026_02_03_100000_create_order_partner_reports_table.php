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
        Schema::create('order_partner_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_id')->constrained('balances')->cascadeOnDelete();
            $table->foreignId('order_report_id')->constrained('order_reports')->cascadeOnDelete();
            $table->date('service_date');
            $table->enum('service_type', ['first_service', 'extra_service']);
            $table->string('service_name', 50);
            $table->foreignId('support_partner_id')->constrained('support_partners')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->string('proof_img', 255);
            $table->string('proof_img2', 255)->nullable();
            $table->enum('report_status', ['draft', 'fixed'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_partner_reports');
    }
};
