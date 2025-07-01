<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->string('reference_number')->nullable()->unique(); // Invoice or PO number from supplier
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('received')->comment('ordered, received, paid, partially_paid, cancelled');
            $table->timestamp('purchase_date');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User who recorded it
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};