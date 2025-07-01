<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) { // Check if table exists
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('staff')->comment("Primary display role, if kept alongside Spatie"); // Optional if fully relying on Spatie
                $table->string('avatar_url')->nullable();
                $table->rememberToken();
                $table->timestamps();
                // $table->softDeletes(); // If you want soft deletes for users
            });
        } else {
            // If table exists, you might want to add the 'role' column if it's missing
            // This would ideally be in a separate "alter_users_table" migration
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role')->default('staff')->after('password')->comment("Primary display role, if kept alongside Spatie");
                }
                if (!Schema::hasColumn('users', 'avatar_url')) {
                    $table->string('avatar_url')->nullable()->after('role');
                }
                // if (!Schema::hasColumn('users', 'deleted_at') && config('features.soft_delete_users')) {
                //     $table->softDeletes();
                // }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        // If you added columns in an alter migration, the down method for that alter migration should drop them.
    }
};