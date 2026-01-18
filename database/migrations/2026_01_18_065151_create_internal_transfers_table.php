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
        Schema::create('internal_transfers', function (Blueprint $table) {
            $table->id();
            $table->date('transfer_date');
            $table->foreignId('balance_id')->constrained('balances')->onDelete('cascade');
            $table->enum('transfer_type', ['transfer_to_cash', 'cash_to_transfer']);
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('proof_img')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transfers');
    }
};
