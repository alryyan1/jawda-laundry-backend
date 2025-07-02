<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        // Protect all report methods with a specific permission.
        // You can get more granular later (e.g., report:view-financial vs. report:view-operational).
        $this->middleware('can:report:view-financial');
    }

    /**
     * Generates a sales summary report for a given date range.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function salesSummary(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'top_services_limit' => 'nullable|integer|min:1|max:20',
        ]);

        // Set default date range to the last 30 days if not provided
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : Carbon::now()->endOfDay();
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : $dateTo->copy()->subDays(29)->startOfDay();
        $topServicesLimit = $validated['top_services_limit'] ?? 10;

        // --- 1. Main KPI Calculations ---
        // We'll base revenue on COMPLETED orders within the date range.
        // The date check is on `updated_at` assuming this is when an order is moved to 'completed'.
        // You could change this to `pickup_date` or `order_date` depending on business rules.
        $completedOrdersQuery = Order::where('status', 'completed')
                                     ->whereBetween('updated_at', [$dateFrom, $dateTo]);
        
        // Clone the query before applying aggregate functions
        $totalRevenue = (clone $completedOrdersQuery)->sum('total_amount');
        $totalOrders = (clone $completedOrdersQuery)->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // --- 2. Top Performing Services Calculation ---
        $topServices = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('service_offerings', 'order_items.service_offering_id', '=', 'service_offerings.id')
            ->join('product_types', 'service_offerings.product_type_id', '=', 'product_types.id')
            ->join('service_actions', 'service_offerings.service_action_id', '=', 'service_actions.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.updated_at', [$dateFrom, $dateTo])
            ->select(
                'service_offerings.id',
                // Use a database-level CONCAT to build the display name
                DB::raw("COALESCE(service_offerings.name_override, CONCAT(product_types.name, ' - ', service_actions.name)) as display_name"),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.sub_total) as total_revenue')
            )
            ->groupBy('service_offerings.id', 'display_name')
            ->orderBy('total_revenue', 'desc')
            ->limit($topServicesLimit)
            ->get();

        // --- 3. Return the consolidated report data ---
        return response()->json([
            'data' => [
                'summary' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders' => (int) $totalOrders,
                    'average_order_value' => (float) $averageOrderValue,
                ],
                'top_services' => $topServices,
                'date_range' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString(),
                ]
            ]
        ]);
    }
}