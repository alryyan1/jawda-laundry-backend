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
        Schema::dropIfExists('composition_items');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('composition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composition_id')->constrained('product_type_compositions')->onDelete('cascade');
            $table->string('item_name'); // اسم العنصر مثل "برجر"، "جبن"، "طماطم"
            $table->text('description')->nullable();
            $table->decimal('quantity', 8, 2)->default(1); // الكمية المطلوبة
            $table->string('unit')->nullable(); // وحدة القياس مثل "قطعة"، "جرام"
            $table->boolean('is_required')->default(true); // هل العنصر مطلوب أم اختياري
            $table->integer('sort_order')->default(0); // ترتيب العناصر
            $table->timestamps();
        });
    }
};
