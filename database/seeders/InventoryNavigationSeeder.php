<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryNavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main inventory navigation item
        $inventoryItemId = DB::table('navigation_items')->insertGetId([
            'key' => 'inventory',
            'title' => json_encode([
                'en' => 'Inventory',
                'ar' => 'المخزون'
            ]),
            'route' => '/inventory',
            'icon' => 'Package',
            'sort_order' => 6, // After orders
            'is_active' => true,
            'is_default' => false,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create inventory management sub-item
        DB::table('navigation_items')->insert([
            'key' => 'inventory-items',
            'title' => json_encode([
                'en' => 'Manage Items',
                'ar' => 'إدارة العناصر'
            ]),
            'route' => '/inventory/items',
            'icon' => 'List',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'parent_id' => $inventoryItemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create inventory transactions sub-item
        DB::table('navigation_items')->insert([
            'key' => 'inventory-transactions',
            'title' => json_encode([
                'en' => 'Transactions',
                'ar' => 'المعاملات'
            ]),
            'route' => '/inventory/transactions',
            'icon' => 'FileText',
            'sort_order' => 2,
            'is_active' => true,
            'is_default' => false,
            'parent_id' => $inventoryItemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create inventory categories sub-item
        DB::table('navigation_items')->insert([
            'key' => 'inventory-categories',
            'title' => json_encode([
                'en' => 'Categories',
                'ar' => 'الفئات'
            ]),
            'route' => '/inventory/categories',
            'icon' => 'Tags',
            'sort_order' => 3,
            'is_active' => true,
            'is_default' => false,
            'parent_id' => $inventoryItemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}