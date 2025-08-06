<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove user navigation permissions for inventory items
        DB::table('user_navigation_permissions')
            ->whereIn('navigation_item_id', function($query) {
                $query->select('id')
                      ->from('navigation_items')
                      ->where('key', 'like', 'inventory%');
            })
            ->delete();

        // Then remove inventory-related navigation items
        DB::table('navigation_items')
            ->where('key', 'like', 'inventory%')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is for removing inventory navigation items
        // No rollback needed as inventory functionality is being removed
    }
};
