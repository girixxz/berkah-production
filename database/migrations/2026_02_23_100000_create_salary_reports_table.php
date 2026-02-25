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
        Schema::create('salary_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balance_id')->constrained('balances')->cascadeOnDelete();
            $table->date('salary_date');
            $table->foreignId('employee_salary_id')->constrained('employee_salaries')->cascadeOnDelete();
            $table->integer('payment_sequence');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->enum('payment_method', ['cash', 'transfer', 'null']);
            $table->string('proof_img', 255)->nullable();
            $table->enum('report_status', ['draft', 'fixed']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_reports');
    }
};
