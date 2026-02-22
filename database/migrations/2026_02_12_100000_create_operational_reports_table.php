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
        Schema::create('operational_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_id')->constrained('balances')->cascadeOnDelete();
            $table->date('operational_date');
            $table->enum('operational_type', ['first_expense', 'extra_expense']);
            $table->enum('category', ['fix_cost_1', 'fix_cost_2', 'printing_supply', 'daily']);
            $table->string('operational_name', 100);
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cash', 'transfer', 'null']);
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
        Schema::dropIfExists('operational_reports');
    }
};
