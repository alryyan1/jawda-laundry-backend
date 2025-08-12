<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Customer;
use App\Models\ServiceOffering;
use App\Models\User;
use App\Services\PricingService; // Assuming you will inject this
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App; // To resolve service from container

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::take(5)->get(); // Get a few customers
        $activeOfferings = ServiceOffering::where('is_active', true)->with(['productType', 'serviceAction'])->get();
        $staffUser = User::where('role', 'staff')->first();
        $pricingService = App::make(PricingService::class); // Resolve PricingService

        if ($customers->isEmpty() || $activeOfferings->isEmpty() || !$staffUser) {
            $this->command->warn('Cannot seed orders: Missing customers, active service offerings, or staff user.');
            return;
        }

        foreach ($customers as $customer) {
            for ($i = 0; $i < rand(1, 3); $i++) { // 1 to 3 orders per customer
                $orderItemsData = [];
                $orderTotalAmount = 0;
                $itemsInOrderCount = rand(1, 4); // 1 to 4 items per order

                for ($j = 0; $j < $itemsInOrderCount; $j++) {
                    $offering = $activeOfferings->random();
                    $quantity = 1;
                    $length = null;
                    $width = null;

                    if ($offering->pricing_strategy === 'dimension_based' || $offering->applicable_unit === 'sq_meter') {
                        $length = rand(100, 500) / 100; // e.g., 1.00 to 5.00 meters
                        $width = rand(100, 300) / 100;  // e.g., 1.00 to 3.00 meters
                        // Quantity might be 1 for most dimension-based items, or could vary
                    } elseif ($offering->pricing_strategy === 'per_unit_product' || $offering->applicable_unit === 'kg') {
                        $quantity = rand(1, 10); // e.g., 1 to 10 kg
                    } else { // fixed price per item
                        $quantity = rand(1, 5);
                    }

                    $priceDetails = $pricingService->calculatePrice(
                        $offering,
                        $customer, // Pass customer for customer-specific pricing
                        $quantity,
                        $length,
                        $width
                    );

                    $orderItemsData[] = [
                        'service_offering_id' => $offering->id,
                        'product_description_custom' => fake()->boolean(30) ? ('Color: ' . fake()->colorName()) : null,
                        'quantity' => $quantity,
                        'length_meters' => $length,
                        'width_meters' => $width,
                        'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                        'sub_total' => $priceDetails['sub_total'],
                        'notes' => fake()->boolean(15) ? fake()->sentence(3) : null,
                    ];
                    $orderTotalAmount += $priceDetails['sub_total'];
                }

                if (empty($orderItemsData)) continue; // Skip if no items somehow

                $orderDate = now()->subDays(rand(0, 60));
                $statusOptions = ['pending', 'processing', 'delivered', 'completed', 'cancelled'];
                $status = $statusOptions[array_rand($statusOptions)];
                
                $paidAmount = 0;
                $paymentStatus = 'pending';
                if ($status === 'completed' || $status === 'delivered') {
                    if (rand(0,1)) { // 50% chance of being paid if ready/completed
                        $paidAmount = $orderTotalAmount;
                        $paymentStatus = 'paid';
                    } else if (rand(0,1) && $orderTotalAmount > 0) { // 25% chance of partially paid
                         $paidAmount = round($orderTotalAmount * (rand(30,70)/100) , 2) ;
                         $paymentStatus = 'partially_paid';
                    }
                }


                Order::create([
                    // order_number removed - using id instead
                    'customer_id' => $customer->id,
                    'user_id' => $staffUser->id,
                    'status' => $status,
                    'total_amount' => $orderTotalAmount,
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paidAmount > 0 ? (rand(0,1) ? 'cash' : 'card') : null,
                    'notes' => fake()->boolean(20) ? fake()->paragraph(1) : null,
                    'order_date' => $orderDate,
                    'due_date' => (clone $orderDate)->addDays(rand(2, 7)),
                    'pickup_date' => ($status === 'completed') ? (clone $orderDate)->addDays(rand(3, 10)) : null,
                ])->items()->createMany($orderItemsData);
            }
        }
    }
}