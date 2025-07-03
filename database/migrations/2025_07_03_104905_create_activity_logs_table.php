<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // This creates `loggable_id` (e.g., 101) and `loggable_type` (e.g., "App\Models\Order")
            $table->morphs('loggable'); 
            $table->text('description'); // The human-readable log message
            $table->foreignId('user_id')->nullable()->comment('User who performed action')->constrained('users')->onDelete('set null');
            $table->json('properties')->nullable(); // To store extra data like old/new values
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('activity_logs'); }
};