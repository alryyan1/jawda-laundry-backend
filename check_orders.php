<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\ProductCategory;

echo "Last 2 orders with category_sequences:\n";

$orders = Order::orderBy('id', 'desc')->take(2)->get(['id', 'order_number', 'category_sequences']);

foreach ($orders as $order) {
    echo "Order #{$order->id} ({$order->order_number}): " . json_encode($order->category_sequences) . "\n";
}

echo "\nProduct Category ID 4 details:\n";
$category = ProductCategory::find(4);
if ($category) {
    echo "Category: {$category->name}\n";
    echo "Sequence Prefix: {$category->sequence_prefix}\n";
    echo "Sequence Enabled: " . ($category->sequence_enabled ? 'Yes' : 'No') . "\n";
    echo "Current Sequence: {$category->current_sequence}\n";
    echo "Next Sequence: {$category->getNextSequence()}\n";
} else {
    echo "Category with ID 4 not found\n";
}

echo "\nDone.\n"; 