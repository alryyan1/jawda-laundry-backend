<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            // Use the order status string as the primary key or a unique key
            $table->string('status')->unique()->comment("Corresponds to an Order status, e.g., 'ready_for_pickup'");
            $table->text('message_template');
            $table->boolean('is_active')->default(true)->comment('Enable/disable sending message for this status');
            $table->boolean('attach_invoice')->default(false)->comment('Should the PDF invoice be sent with this message?');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('whatsapp_templates'); }
};