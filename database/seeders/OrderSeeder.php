<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        $services = Service::all();
        $staffUser = User::where('email', 'staff@laundry.com')->first();

        if ($customers->isEmpty() || $services->isEmpty() || !$staffUser) {
            $this->command->info('Cannot seed orders: Missing customers, services, or staff user.');
            return;
        }

        for ($i = 0; $i < 5; $i++) { // Create 5 sample orders
            $customer = $customers->random();
            $orderServices = $services->random(rand(1, 3)); // 1 to 3 services per order
            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($orderServices as $service) {
                $quantity = rand(1, 5);
                $priceAtOrder = $service->price;
                $subTotal = $quantity * $priceAtOrder;
                $totalAmount += $subTotal;

                $orderItemsData[] = [
                    'service_id' => $service->id,
                    'quantity' => $quantity,
                    'price_at_order' => $priceAtOrder,
                    'sub_total' => $subTotal,
                ];
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'user_id' => $staffUser->id,
                'status' => ['pending', 'processing', 'ready_for_pickup', 'completed'][array_rand(['pending', 'processing', 'ready_for_pickup', 'completed'])],
                'total_amount' => $totalAmount,
                'notes' => 'Sample order notes for ' . $customer->name,
                'order_date' => now()->subDays(rand(1, 30)),
                'due_date' => now()->subDays(rand(1,30))->addDays(rand(2,5)),
            ]);

            $order->items()->createMany($orderItemsData);
        }
    }
}