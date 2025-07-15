<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Table 1", "VIP Table", "Outdoor Table 1"
            $table->string('number')->unique(); // e.g., "T1", "VIP1", "OUT1"
            $table->integer('capacity')->default(4); // Number of seats
            $table->text('description')->nullable(); // Additional details
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add table_id to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('customer_id')->constrained('restaurant_tables')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn('table_id');
        });
        
        Schema::dropIfExists('restaurant_tables');
    }
}; 