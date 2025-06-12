<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration // Or `class CreateOrderItemsTable extends Migration` if older Laravel
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // Link to the new ServiceOffering model
            $table->foreignId('service_offering_id')->constrained('service_offerings')->onDelete('cascade');

            // Optional: for customer-provided description of the specific item (e.g., "Blue Silk Blouse with Coffee Stain")
            $table->string('product_description_custom')->nullable();

            $table->integer('quantity')->default(1); // Default to 1, can be overridden

            // For dimension-based pricing (e.g., carpets)
            $table->decimal('length_meters', 8, 2)->nullable();
            $table->decimal('width_meters', 8, 2)->nullable();

            // This will store the price PER UNIT (e.g., per item, or per sq meter) calculated at the time of order
            // It could come from ServiceOffering.default_price, ServiceOffering.default_price_per_sq_meter, or a PricingRule
            $table->decimal('calculated_price_per_unit_item', 10, 2);

            // This is the total for this line item (quantity * calculated_price_per_unit_item OR area * calculated_price_per_unit_item)
            $table->decimal('sub_total', 10, 2);

            // Specific notes for this particular item in the order
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};