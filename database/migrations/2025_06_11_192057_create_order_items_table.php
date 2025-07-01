```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('service_offering_id')->constrained('service_offerings')->onDelete('restrict'); // Prevent deleting SO if in use

            $table->string('product_description_custom')->nullable();
            $table->integer('quantity')->unsigned()->default(1);
            $table->decimal('length_meters', 8, 2)->unsigned()->nullable();
            $table->decimal('width_meters', 8, 2)->unsigned()->nullable();

            $table->decimal('calculated_price_per_unit_item', 10, 2);
            $table->decimal('sub_total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
