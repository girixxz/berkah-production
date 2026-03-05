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
            $table->timestamp('loan_date')->useCurrent();
            $table->foreignId('balance_id')->constrained('balances')->onDelete('cascade');

            $table->enum('payment_method', ['transfer', 'cash']);
            $table->decimal('loan_amount', 12, 2);

            $table->string('proof_img', 255);
            $table->enum('status', ['outstanding', 'paid_off'])->default('outstanding');
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
