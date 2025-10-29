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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->timestamp('paid_at')->useCurrent();
            
            $table->enum('payment_method', ['tranfer', 'cash']);
            $table->enum('payment_type', ['dp', 'repayment', 'full_payment']);
            $table->decimal('amount', 12, 2);
            
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->text('notes')->nullable();
            $table->text('img_url')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
