<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteAllOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:delete-all 
                            {--force : Force deletion without confirmation}
                            {--soft : Use soft delete (if SoftDeletes is enabled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all orders from the database. This will also delete related order items and payments.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $soft = $this->option('soft');

        // Get total count of orders
        $orderCount = Order::count();
        
        if ($orderCount === 0) {
            $this->info('No orders found in the database.');
            return Command::SUCCESS;
        }

        // Show warning and get confirmation
        if (!$force) {
            $this->warn('⚠️  WARNING: This will delete ALL orders from the database!');
            $this->warn("   Total orders to delete: {$orderCount}");
            $this->warn('   This will also delete all related order items and payments.');
            
            if (!$this->confirm('Do you want to continue?', false)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("Starting deletion of {$orderCount} orders...");

        DB::beginTransaction();
        try {
            // Get order IDs first
            $orderIds = Order::pluck('id');
            
            // Delete payments first (due to foreign key constraints)
            $paymentCount = Payment::whereIn('order_id', $orderIds)->count();
            if ($paymentCount > 0) {
                $this->info("Deleting {$paymentCount} payments...");
                Payment::whereIn('order_id', $orderIds)->delete();
                $this->info("✓ Deleted {$paymentCount} payments.");
            }

            // Delete order items
            $itemCount = OrderItem::whereIn('order_id', $orderIds)->count();
            if ($itemCount > 0) {
                $this->info("Deleting {$itemCount} order items...");
                OrderItem::whereIn('order_id', $orderIds)->delete();
                $this->info("✓ Deleted {$itemCount} order items.");
            }

            // Delete orders
            if ($soft) {
                // Use soft delete if SoftDeletes trait is enabled
                Order::query()->delete();
                $this->info("✓ Soft deleted {$orderCount} orders.");
            } else {
                // Hard delete - use truncate for better performance, but this won't work with foreign keys
                // So we'll use delete instead
                Order::query()->delete();
                $this->info("✓ Deleted {$orderCount} orders.");
            }

            DB::commit();

            $this->newLine();
            $this->info('✓ Successfully deleted all orders and related data!');
            
            Log::info('All orders deleted via command', [
                'deleted_by' => 'console_command',
                'order_count' => $orderCount,
                'payment_count' => $paymentCount,
                'item_count' => $itemCount,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error deleting orders: ' . $e->getMessage());
            Log::error('Error deleting all orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
