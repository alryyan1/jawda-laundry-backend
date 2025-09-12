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
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'product_description_custom',
                'length_meters',
                'width_meters',
                'calculated_price_per_unit_item'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_description_custom')->nullable();
            $table->decimal('length_meters', 8, 2)->unsigned()->nullable();
            $table->decimal('width_meters', 8, 2)->unsigned()->nullable();
            $table->decimal('calculated_price_per_unit_item', 10, 2);
        });
    }
};
