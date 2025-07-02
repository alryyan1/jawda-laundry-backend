<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'company_name', 'whatsapp_api_token'
            $table->text('value')->nullable(); // The setting value
            $table->string('group')->default('general')->comment('e.g., general, whatsapp, payment');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};