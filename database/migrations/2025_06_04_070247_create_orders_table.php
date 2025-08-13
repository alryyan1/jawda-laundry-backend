<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict'); // Prevent deleting customer with orders
            $table->foreignId('user_id')->comment('Staff member who created/processed order')->nullable()->constrained('users')->onDelete('set null');

            $table->string('status')->default('pending'); // pending, processing, delivered, completed, cancelled
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->string('payment_status')->default('pending')->comment('pending, paid, partially_paid, refunded');
            $table->string('payment_method')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('order_date')->useCurrent();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('pickup_date')->nullable();
            $table->timestamp('delivered_date')->nullable();
            $table->text('delivery_address')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Recommended for orders
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
