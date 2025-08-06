<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class RecalculateOrderTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:recalculate-totals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate total_amount for all orders based on their order items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting order total recalculation...');

        $orders = Order::with('items')->get();
        $updatedCount = 0;
        $totalOrders = $orders->count();

        $progressBar = $this->output->createProgressBar($totalOrders);
        $progressBar->start();

        foreach ($orders as $order) {
            $oldTotal = (float) $order->getAttribute('total_amount');
            $calculatedTotal = $order->calculated_total_amount;
            
            if ($oldTotal != $calculatedTotal) {
                $order->setAttribute('total_amount', $calculatedTotal);
                $order->save();
                $updatedCount++;
                
                $this->line("\nOrder #{$order->id}: {$oldTotal} → {$calculatedTotal}");
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("Recalculation completed!");
        $this->info("Total orders processed: {$totalOrders}");
        $this->info("Orders updated: {$updatedCount}");

        return 0;
    }
} 