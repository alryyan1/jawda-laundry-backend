<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class PopulateDailyOrderNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all orders grouped by date
        $ordersByDate = Order::select('id', 'created_at')
            ->whereNull('daily_order_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($order) {
                return $order->created_at->format('Y-m-d');
            });

        foreach ($ordersByDate as $date => $orders) {
            $dailyNumber = 1;
            foreach ($orders as $order) {
                $order->update(['daily_order_number' => $dailyNumber]);
                $dailyNumber++;
            }
        }

        $this->command->info('Daily order numbers populated successfully!');
    }
}
