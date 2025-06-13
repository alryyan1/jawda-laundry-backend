<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
}