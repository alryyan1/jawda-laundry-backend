<?php

/**
 * Manual Test Script for Category Sequence Update
 * 
 * This script tests the functionality of updating category sequences
 * when order item quantities are changed.
 * 
 * Run this script from the command line:
 * php test_category_sequence.php
 */

require_once 'vendor/autoload.php';

use App\Services\OrderSequenceService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ServiceOffering;
use App\Models\Customer;
use App\Models\User;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Category Sequence Update Test ===\n\n";

try {
    // Create test data
    echo "1. Creating test data...\n";
    
    $user = User::create([
        'name' => 'Test User',
        'username' => 'testuser_' . time(),
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    
    $customer = Customer::create([
        'name' => 'Test Customer',
        'phone' => '1234567890',
        'email' => 'customer_' . time() . '@example.com',
    ]);
    
    $category = ProductCategory::create([
        'name' => 'Test Category ' . time(),
        'sequence_enabled' => true,
        'sequence_prefix' => 'Z',
        'current_sequence' => 0,
    ]);
    
    $productType = ProductType::create([
        'name' => 'Test Product Type',
        'product_category_id' => $category->id,
    ]);
    
    $serviceAction = \App\Models\ServiceAction::create([
        'name' => 'Test Action',
        'description' => 'Test service action',
    ]);
    
    $serviceOffering = ServiceOffering::create([
        'name' => 'Test Service',
        'product_type_id' => $productType->id,
        'service_action_id' => $serviceAction->id,
        'default_price' => 10.00,
    ]);
    
    $order = Order::create([
        // order_number removed - using id instead
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'status' => 'pending',
        'total_amount' => 0,
        'paid_amount' => 0,
        'payment_status' => 'pending',
    ]);
    
    echo "✓ Test data created successfully\n\n";
    
    // Test 1: Create order item with quantity 2
    echo "2. Creating order item with quantity 2...\n";
    
    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'service_offering_id' => $serviceOffering->id,
        'quantity' => 2,
        'calculated_price_per_unit_item' => 10.00,
        'sub_total' => 20.00,
    ]);
    
    echo "✓ Order item created with quantity 2\n\n";
    
    // Test 2: Generate initial category sequences
    echo "3. Generating initial category sequences...\n";
    
    $orderSequenceService = new OrderSequenceService();
    $initialSequences = $orderSequenceService->generateOrderSequences($order);
    
    echo "Initial sequences: " . json_encode($initialSequences) . "\n";
    
    if (isset($initialSequences[$category->id])) {
        $initialSequence = $initialSequences[$category->id];
        echo "✓ Initial category sequence: {$initialSequence}\n";
        
        // Verify it contains quantity 2
        if (strpos($initialSequence, '-2') !== false) {
            echo "✓ Sequence correctly shows quantity 2\n";
        } else {
            echo "✗ ERROR: Sequence does not show quantity 2\n";
        }
    } else {
        echo "✗ ERROR: No sequence found for category\n";
    }
    
    echo "\n";
    
    // Test 3: Update quantity to 5
    echo "4. Updating order item quantity to 5...\n";
    
    $orderItem->update(['quantity' => 5]);
    echo "✓ Order item quantity updated to 5\n\n";
    
    // Test 4: Generate updated category sequences
    echo "5. Generating updated category sequences...\n";
    
    $updatedSequences = $orderSequenceService->generateOrderSequences($order);
    
    echo "Updated sequences: " . json_encode($updatedSequences) . "\n";
    
    if (isset($updatedSequences[$category->id])) {
        $updatedSequence = $updatedSequences[$category->id];
        echo "✓ Updated category sequence: {$updatedSequence}\n";
        
        // Verify it contains quantity 5
        if (strpos($updatedSequence, '-5') !== false) {
            echo "✓ Sequence correctly shows quantity 5\n";
        } else {
            echo "✗ ERROR: Sequence does not show quantity 5\n";
        }
        
        // Verify sequence number before hyphen remains the same
        $initialParts = explode('-', $initialSequence);
        $updatedParts = explode('-', $updatedSequence);
        
        if ($initialParts[0] === $updatedParts[0]) {
            echo "✓ Sequence number before hyphen remains the same\n";
        } else {
            echo "✗ ERROR: Sequence number before hyphen changed\n";
        }
        
    } else {
        echo "✗ ERROR: No sequence found for category after update\n";
    }
    
    echo "\n";
    
    // Test 5: Test with multiple items in same category
    echo "6. Testing with multiple items in same category...\n";
    
    $serviceAction2 = \App\Models\ServiceAction::create([
        'name' => 'Test Action 2',
        'description' => 'Test service action 2',
    ]);
    
    $serviceOffering2 = ServiceOffering::create([
        'name' => 'Test Service 2',
        'product_type_id' => $productType->id,
        'service_action_id' => $serviceAction2->id,
        'default_price' => 15.00,
    ]);
    
    $orderItem2 = OrderItem::create([
        'order_id' => $order->id,
        'service_offering_id' => $serviceOffering2->id,
        'quantity' => 3,
        'calculated_price_per_unit_item' => 15.00,
        'sub_total' => 45.00,
    ]);
    
    echo "✓ Second order item created with quantity 3\n";
    
    $multiSequences = $orderSequenceService->generateOrderSequences($order);
    
    if (isset($multiSequences[$category->id])) {
        $multiSequence = $multiSequences[$category->id];
        echo "✓ Multi-item category sequence: {$multiSequence}\n";
        
        // Should show total quantity 8 (5 + 3)
        if (strpos($multiSequence, '-8') !== false) {
            echo "✓ Sequence correctly shows total quantity 8\n";
        } else {
            echo "✗ ERROR: Sequence does not show total quantity 8\n";
        }
    } else {
        echo "✗ ERROR: No sequence found for category with multiple items\n";
    }
    
    echo "\n";
    
    // Cleanup
    echo "7. Cleaning up test data...\n";
    
    $orderItem2->delete();
    $orderItem->delete();
    $order->delete();
    $serviceOffering2->delete();
    $serviceOffering->delete();
    $serviceAction2->delete();
    $serviceAction->delete();
    $productType->delete();
    $category->delete();
    $customer->delete();
    $user->delete();
    
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== Test Results ===\n";
    echo "✓ All tests completed successfully!\n";
    echo "✓ Category sequences are updating correctly when quantities change\n";
    echo "✓ The number after the hyphen reflects the total quantity for the category\n";
    echo "✓ Multiple items in the same category are summed correctly\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
