<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;

class InventoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get category IDs
        $detergentsCategory = InventoryCategory::where('name', 'Detergents & Soaps')->first();
        $bleachCategory = InventoryCategory::where('name', 'Bleach & Stain Removers')->first();
        $fabricCategory = InventoryCategory::where('name', 'Fabric Care')->first();
        $packagingCategory = InventoryCategory::where('name', 'Packaging Materials')->first();

        // Get product types that don't already have inventory items
        $productTypesWithoutInventory = \App\Models\ProductType::whereDoesntHave('inventoryItem')->get();
        
        if ($productTypesWithoutInventory->isEmpty()) {
            $this->command->info('All product types already have inventory items. Skipping seeder.');
            return;
        }
        
        $items = [
            [
                'sku' => 'DET-001',
                'description' => 'High-quality laundry detergent powder for all types of fabrics',
                'unit' => 'kg',
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'current_stock' => 25,
                'cost_per_unit' => 15.50,
                'is_active' => true
            ],
            [
                'sku' => 'SOFT-001',
                'description' => 'Gentle fabric softener for soft and fresh-smelling clothes',
                'unit' => 'liters',
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'current_stock' => 12,
                'cost_per_unit' => 8.75,
                'is_active' => true
            ],
            [
                'sku' => 'BLEACH-001',
                'description' => 'Strong chlorine bleach for whitening and stain removal',
                'unit' => 'liters',
                'min_stock_level' => 3,
                'max_stock_level' => 30,
                'current_stock' => 8,
                'cost_per_unit' => 12.00,
                'is_active' => true
            ],
            [
                'sku' => 'HANG-001',
                'description' => 'Plastic hangers for hanging clothes',
                'unit' => 'pieces',
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'current_stock' => 250,
                'cost_per_unit' => 0.50,
                'is_active' => true
            ],
            [
                'sku' => 'BAG-001',
                'description' => 'Plastic bags for packaging clean clothes',
                'unit' => 'pieces',
                'min_stock_level' => 500,
                'max_stock_level' => 5000,
                'current_stock' => 1200,
                'cost_per_unit' => 0.15,
                'is_active' => true
            ],
            [
                'sku' => 'STAIN-001',
                'description' => 'Pre-treatment stain remover spray',
                'unit' => 'bottles',
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'current_stock' => 35,
                'cost_per_unit' => 6.25,
                'is_active' => true
            ]
        ];

        // Create inventory items for product types that don't have them
        foreach ($items as $index => $item) {
            if ($index < $productTypesWithoutInventory->count()) {
                $productType = $productTypesWithoutInventory[$index];
                $item['product_type_id'] = $productType->id;
                InventoryItem::create($item);
            }
        }
    }
}
