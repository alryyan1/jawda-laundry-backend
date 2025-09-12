<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateShiftsFromOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --from=YYYY-MM-DD   Only consider orders created on/after this date
     * --to=YYYY-MM-DD     Only consider orders created on/before this date
     * --assign            Assign orders on that date to the created/existing shift
     */
    protected $signature = 'shifts:generate-from-orders {--from=} {--to=} {--assign}';

    /**
     * The console command description.
     */
    protected $description = 'Create one shift per distinct order creation date, without duplicates, with optional date filtering and assignment';

    public function handle(): int
    {
        $fromDate = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $toDate = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : null;
        $shouldAssign = (bool) $this->option('assign');

        $this->info('Scanning orders for distinct creation dates...');

        $ordersQuery = Order::query();
        if ($fromDate) {
            $ordersQuery->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $ordersQuery->where('created_at', '<=', $toDate);
        }

        $dates = $ordersQuery
            ->select(DB::raw('DATE(created_at) as order_date'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->pluck('order_date');

        if ($dates->isEmpty()) {
            $this->info('No orders found for the specified range. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $dates->count() . ' distinct order date(s).');

        $createdCount = 0;
        $existingCount = 0;
        $assignedOrders = 0;

        foreach ($dates as $date) {
            $openAt = Carbon::parse($date)->startOfDay();
            $closeAt = Carbon::parse($date)->endOfDay();

            // Consider a shift matching this date if opened_at is within this day
            $shift = Shift::whereDate('opened_at', $openAt->toDateString())->first();

            if (!$shift) {
                $shift = Shift::create([
                    'opened_at' => $openAt,
                    'closed_at' => $closeAt,
                    'opening_cash' => 0,
                    'closing_cash' => 0,
                    'notes' => 'Auto-created from orders date: ' . $openAt->toDateString(),
                ]);
                $createdCount++;
                $this->line('Created shift for ' . $openAt->toDateString());
            } else {
                $existingCount++;
                $this->line('Shift already exists for ' . $openAt->toDateString());
            }

            if ($shouldAssign) {
                $affected = Order::whereDate('created_at', $openAt->toDateString())
                    ->where(function ($q) use ($shift) {
                        $q->whereNull('shift_id')->orWhere('shift_id', '!=', $shift->id);
                    })
                    ->update(['shift_id' => $shift->id]);
                $assignedOrders += $affected;
            }
        }

        $this->info("Shifts created: {$createdCount}, existing: {$existingCount}");
        if ($shouldAssign) {
            $this->info("Orders assigned to shifts: {$assignedOrders}");
        }

        return self::SUCCESS;
    }
}


