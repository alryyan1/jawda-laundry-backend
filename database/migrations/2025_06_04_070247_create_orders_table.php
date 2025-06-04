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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_number')->unique(); // Auto-generate this
        $table->foreignId('customer_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->comment('Staff member who created/processed order')->nullable()->constrained()->onDelete('set null');
        $table->enum('status', ['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])->default('pending');
        $table->decimal('total_amount', 10, 2)->default(0.00);
        $table->decimal('paid_amount', 10, 2)->default(0.00);
        $table->text('notes')->nullable();
        $table->timestamp('order_date')->useCurrent();
        $table->timestamp('due_date')->nullable();
        $table->timestamp('pickup_date')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
