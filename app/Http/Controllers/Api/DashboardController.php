<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $readyForPickupOrders = Order::where('status', 'ready_for_pickup')->count();
        $completedTodayOrders = Order::where('status', 'completed')
                                    ->whereDate('updated_at', Carbon::today()) // Assuming updated_at reflects completion time
                                    ->count();
        $totalActiveCustomers = Customer::count(); // Define "active" if needed, e.g., with recent orders

        // Example: Revenue this month (sum of total_amount for completed orders)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlyRevenue = Order::where('status', 'completed')
                              ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                              ->sum('total_amount');

        return response()->json([
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'readyForPickupOrders' => $readyForPickupOrders,
            'completedTodayOrders' => $completedTodayOrders,
            'totalActiveCustomers' => $totalActiveCustomers,
            'monthlyRevenue' => (float) $monthlyRevenue,
        ]);
    }
    public function ordersTrend(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $trend = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date'); // Key by date for easy lookup

        // Fill in missing dates with 0 count
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dates->put($date, [
                'date' => $date,
                'count' => $trend->get($date, ['count' => 0])['count']
            ]);
        }

        return response()->json(['data' => $dates->values()]);
    }

    public function revenueBreakdown(Request $request)
    {
        $breakdown = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('service_offerings', 'order_items.service_offering_id', '=', 'service_offerings.id')
            ->join('product_types', 'service_offerings.product_type_id', '=', 'product_types.id')
            ->join('product_categories', 'product_types.product_category_id', '=', 'product_categories.id')
            ->where('orders.status', 'completed') // Only consider completed orders for revenue
            ->select('product_categories.name', DB::raw('SUM(order_items.sub_total) as total_revenue'))
            ->groupBy('product_categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        return response()->json(['data' => $breakdown]);
    }
}