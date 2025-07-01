<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('base_duration_minutes')->unsigned()->nullable();
            // $table->boolean('is_active')->default(true); // If actions can be deactivated
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_actions');
    }
};