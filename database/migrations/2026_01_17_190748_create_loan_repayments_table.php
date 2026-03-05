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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('paid_date')->useCurrent();
            $table->foreignId('loan_id')->constrained('loan_capitals')->onDelete('cascade');
            $table->foreignId('balance_id')->constrained('balances')->onDelete('cascade');
            $table->enum('payment_method', ['transfer', 'cash']);
            $table->decimal('amount', 12, 2);
            $table->string('proof_img', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
