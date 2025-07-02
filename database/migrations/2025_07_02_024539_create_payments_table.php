<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->comment('Staff who recorded payment')->constrained('users')->onDelete('set null');
            
            $table->decimal('amount', 10, 2); // Can be positive for payment, negative for refund
            $table->string('method')->comment('e.g., cash, card, online, credit');
            $table->string('type')->default('payment')->comment('payment, refund');
            
            $table->string('transaction_id')->nullable()->comment('For card or online payments');
            $table->text('notes')->nullable();
            $table->timestamp('payment_date')->useCurrent();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};