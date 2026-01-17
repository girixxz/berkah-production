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
        Schema::create('loan_capitals', function (Blueprint $table) {
            $table->id();
            $table->string('loan_code', 50)->unique();
            $table->date('loan_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->string('proof_img', 255);
            $table->enum('status', ['outstanding', 'done'])->default('outstanding');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_capitals');
    }
};
