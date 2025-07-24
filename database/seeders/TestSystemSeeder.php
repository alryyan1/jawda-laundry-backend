<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\ServiceOffering;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Services\PricingService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class TestSystemSeeder extends Seeder
{
    /**
     * Seed the application's database with a large amount of test data.
     */
    public function run(): void
    {
        $this->command->info('Starting test system seeder...');

        // === Create Users ===
        // Ensure we have users for each role to assign as creators
        if (User::where('role', 'processor')->count() < 2) {
            User::factory()->count(2)->create(['role' => 'processor']);
        }
        if (User::where('role', 'delivery')->count() < 2) {
            User::factory()->count(2)->create(['role' => 'delivery']);
        }
        $staffUsers = User::whereIn('role', ['receptionist', 'admin'])->get();
        if ($staffUsers->isEmpty()) {
            $this->command->error('No admin or receptionist users found. Please run UserSeeder and PermissionSeeder first.');
            return;
        }

        // === Create Customers ===
        if (Customer::count() < 50) {
            Customer::factory()->count(50)->create(['user_id' => $staffUsers->random()->id]);
            $this->command->info('50 customers created.');
        }

        // === Create Expense Categories & Expenses ===
        $expenseCategories = ['Supplies', 'Utilities', 'Rent', 'Salaries', 'Maintenance', 'Marketing'];
        foreach ($expenseCategories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat]);
        }
        if (Expense::count() < 100) {
            Expense::factory()->count(100)->create();
            $this->command->info('100 expenses created.');
        }

        // === Create Suppliers & Purchases ===
        if (Supplier::count() < 15) {
            Supplier::factory()->count(15)->create();
        }
        $suppliers = Supplier::all();
        if (Purchase::count() < 40) {
            foreach ($suppliers as $supplier) {
                for ($i = 0; $i < rand(1, 4); $i++) {
                    $purchaseDate = fake()->dateTimeBetween('-1 year', 'now');
                    $itemsData = [];
                    $totalAmount = 0;
                    for ($j=0; $j < rand(1, 8) ; $j++) {
                        $qty = rand(1, 20);
                        $price = fake()->randomFloat(2, 5, 100);
                        $subTotal = $qty * $price;
                        $productTypeId = \App\Models\ProductType::inRandomOrder()->value('id');
                        $itemsData[] = [
                            'product_type_id' => $productTypeId,
                            'quantity' => $qty,
                            'unit_price' => $price,
                            'sub_total' => $subTotal,
                        ];
                        $totalAmount += $subTotal;
                    }
                    $purchase = Purchase::create([
                        'supplier_id' => $supplier->id,
                        'reference_number' => 'PO-' . fake()->unique()->numberBetween(1000, 9999),
                        'total_amount' => $totalAmount,
                        'status' => fake()->randomElement(['ordered', 'received', 'paid']),
                        'purchase_date' => $purchaseDate,
                        'user_id' => $staffUsers->random()->id,
                    ]);
                    $purchase->items()->createMany($itemsData);
                }
            }
            $this->command->info('~40 purchases with items created.');
        }


        // === Create a large number of Orders ===
        if (Order::count() < 200) {
            $pricingService = App::make(PricingService::class);
            $customers = Customer::all();
            $activeOfferings = ServiceOffering::where('is_active', true)->with('productType')->get();

            if ($activeOfferings->isEmpty()) {
                $this->command->error('No active service offerings found. Cannot create orders.');
                return;
            }

            for ($k = 0; $k < 200; $k++) {
                $customer = $customers->random();
                $orderDate = fake()->dateTimeBetween('-1 year', 'now');
                $itemsInOrderCount = rand(1, 10);
                $orderItemsData = [];
                $orderTotalAmount = 0;

                for ($j = 0; $j < $itemsInOrderCount; $j++) {
                    $offering = $activeOfferings->random();
                    $quantity = 1;
                    $length = null;
                    $width = null;

                    if ($offering->productType->is_dimension_based) {
                        $length = fake()->randomFloat(2, 1, 5);
                        $width = fake()->randomFloat(2, 1, 4);
                    } else {
                        $quantity = rand(1, 8);
                    }

                    $priceDetails = $pricingService->calculatePrice($offering, $customer, $quantity, $length, $width);
                    $orderItemsData[] = [
                        'service_offering_id' => $offering->id,
                        'product_description_custom' => fake()->boolean(20) ? 'Brand: ' . fake()->company() : null,
                        'quantity' => $quantity,
                        'length_meters' => $length,
                        'width_meters' => $width,
                        'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                        'sub_total' => $priceDetails['sub_total'],
                    ];
                    $orderTotalAmount += $priceDetails['sub_total'];
                }

                if (empty($orderItemsData)) continue;

                $status = fake()->randomElement(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled']);
                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                    'customer_id' => $customer->id,
                    'user_id' => $staffUsers->random()->id,
                    'status' => $status,
                    'total_amount' => $orderTotalAmount,
                    'paid_amount' => ($status === 'completed' && rand(0, 1)) ? $orderTotalAmount : 0,
                    'payment_status' => ($status === 'completed' && rand(0, 1)) ? 'paid' : 'pending',
                    'order_date' => $orderDate,
                    'due_date' => (clone $orderDate)->addDays(rand(2, 5)),
                    'pickup_date' => ($status === 'completed') ? (clone $orderDate)->addDays(rand(3, 10)) : null,
                ]);
                $order->items()->createMany($orderItemsData);

                // Progress indicator
                if (($k + 1) % 20 == 0) {
                    $this->command->info("Created " . ($k + 1) . "/200 orders...");
                }
            }
            $this->command->info('200 orders with items created.');
        }

        $this->command->info('Test system seeding completed!');
    }
}