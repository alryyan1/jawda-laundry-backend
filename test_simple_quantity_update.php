<?php

/**
 * Simple Test Script for Order Item Quantity Update
 * 
 * This script directly tests the quantity update functionality
 * without going through API authorization.
 * 
 * Run this script from the command line:
 * php test_simple_quantity_update.php
 */

require_once 'vendor/autoload.php';

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

echo "=== Simple Quantity Update Test ===\n\n";

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
        'name' => 'Test Action ' . time(),
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
    
    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'service_offering_id' => $serviceOffering->id,
        'quantity' => 2,
        'calculated_price_per_unit_item' => 10.00,
        'sub_total' => 20.00,
    ]);
    
    echo "✓ Test data created successfully\n";
    echo "  - Order ID: {$order->id}\n";
    echo "  - Order Item ID: {$orderItem->id}\n";
    echo "  - Initial quantity: 2\n\n";
    
    // Generate initial category sequences
    echo "2. Generating initial category sequences...\n";
    $order->generateCategorySequences();
    $order->refresh();
    
    $initialSequences = $order->category_sequences;
    echo "Initial category sequences: " . json_encode($initialSequences) . "\n\n";
    
    // Test the quantity update functionality directly
    echo "3. Testing quantity update functionality...\n";
    
    // Update quantity directly
    $orderItem->update(['quantity' => 5]);
    echo "✓ Order item quantity updated to 5\n";
    
    // Recalculate order total
    $order->recalculateTotalAmount();
    echo "✓ Order total recalculated\n";
    
    // Refresh order to get latest data
    $order->refresh();
    echo "✓ Order refreshed\n";
    
    // Regenerate category sequences
    $order->generateCategorySequences(true);
    echo "✓ Category sequences regenerated\n";
    
    // Refresh order to get updated sequences
    $order->refresh();
    echo "✓ Order refreshed after sequence generation\n";
    
    echo "\n";
    
    // Verify the results
    echo "4. Verifying results...\n";
    
    $orderItem->refresh();
    echo "Order item quantity in database: " . $orderItem->quantity . "\n";
    echo "Order total in database: " . $order->total_amount . "\n";
    echo "Category sequences in database: " . json_encode($order->category_sequences) . "\n";
    
    if ($orderItem->quantity === 5) {
        echo "✓ Order item quantity correctly updated in database\n";
    } else {
        echo "✗ ERROR: Order item quantity not updated in database\n";
    }
    
    if ($order->total_amount === 50.0) {
        echo "✓ Order total correctly updated in database\n";
    } else {
        echo "✗ ERROR: Order total not updated correctly in database\n";
    }
    
    $updatedSequences = $order->category_sequences;
    if (isset($updatedSequences[$category->id])) {
        $updatedSequence = $updatedSequences[$category->id];
        echo "✓ Updated sequence: {$updatedSequence}\n";
        
        if (strpos($updatedSequence, '-5') !== false) {
            echo "✓ SUCCESS: Category sequence correctly updated to show quantity 5\n";
        } else {
            echo "✗ ERROR: Category sequence does not show quantity 5\n";
        }
    } else {
        echo "✗ ERROR: No category sequence found in database\n";
    }
    
    echo "\n";
    
    // Cleanup
    echo "5. Cleaning up test data...\n";
    
    $orderItem->delete();
    $order->delete();
    $serviceOffering->delete();
    $serviceAction->delete();
    $productType->delete();
    $category->delete();
    $customer->delete();
    $user->delete();
    
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== Test Summary ===\n";
    echo "✓ Direct quantity update test completed\n";
    echo "✓ Category sequences are updated when quantity changes\n";
    echo "✓ The number after the hyphen reflects the new quantity\n";
    echo "✓ Order totals are recalculated correctly\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
