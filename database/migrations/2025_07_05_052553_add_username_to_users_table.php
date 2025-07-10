<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                // Add the new username column. Make it unique.
                // It can be nullable initially if you have existing users,
                // then you can write a script to populate it. For a fresh start, make it non-nullable.
                $table->string('username')->unique()->after('name');
            }
            // Make email nullable if it's no longer the primary identifier for login
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
             if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique()->change(); // Revert email to be unique
            }
        });
    }
};