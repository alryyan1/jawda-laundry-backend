<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $pendingOrders = Order::where('status', 'pending')->count();
        $processingOrders = Order::where('status', 'processing')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();
        $completedTodayOrders = Order::where('status', 'completed')
                                    ->whereDate('updated_at', Carbon::today()) // Assuming updated_at reflects completion time
                                    ->count();
        $cancelledOrders = Order::where('status', 'cancelled')->count();
        $totalActiveCustomers = Customer::count(); // Define "active" if needed, e.g., with recent orders

        // Orders count by day, week, month
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalOrdersToday = Order::whereDate('created_at', $today)->count();
        $totalOrdersThisWeek = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $totalOrdersThisMonth = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        // Paid totals (sum of payments.amount) by day, week, month
        $payments = DB::table('payments')->where('type', 'payment');
        $totalRevenueToday = (clone $payments)
            ->whereDate('payment_date', $today)
            ->sum('amount');
        $totalRevenueThisWeek = (clone $payments)
            ->whereBetween('payment_date', [$startOfWeek, $endOfWeek])
            ->sum('amount');
        $totalRevenueThisMonth = (clone $payments)
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Back-compat monthly revenue (keep existing field)
        $monthlyRevenue = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        return response()->json([
            // New fields expected by the frontend
            'totalOrdersToday' => (int) $totalOrdersToday,
            'totalOrdersThisWeek' => (int) $totalOrdersThisWeek,
            'totalOrdersThisMonth' => (int) $totalOrdersThisMonth,

            'totalRevenueToday' => (float) $totalRevenueToday,
            'totalRevenueThisWeek' => (float) $totalRevenueThisWeek,
            'totalRevenueThisMonth' => (float) $totalRevenueThisMonth,

            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'deliveredOrders' => $deliveredOrders,
            'completedTodayOrders' => $completedTodayOrders,
            'cancelledOrders' => $cancelledOrders,
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

    public function topProducts(Request $request)
    {
        // Top 5 requested products by item count (grouped by product type)
        $results = DB::table('order_items')
            ->join('service_offerings', 'order_items.service_offering_id', '=', 'service_offerings.id')
            ->join('product_types', 'service_offerings.product_type_id', '=', 'product_types.id')
            ->select('product_types.name as name', DB::raw('COUNT(*) as count'))
            ->groupBy('product_types.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $total = (int) $results->sum('count');
        $data = $results->map(function ($row) use ($total) {
            $count = (int) $row->count;
            $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
            return [
                'name' => $row->name,
                'count' => $count,
                'percentage' => $percentage,
            ];
        });

        return response()->json(['data' => $data]);
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

    public function orderItemsTrend(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();

        $trend = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('DATE(orders.created_at) as date, COUNT(*) as count, SUM(order_items.quantity) as totalQuantity')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date'); // Key by date for easy lookup

        // Fill in missing dates with 0 count
        $dates = collect();
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $dateData = $trend->get($date, (object)['count' => 0, 'totalQuantity' => 0]);
            $dates->put($date, [
                'date' => $date,
                'count' => $dateData->count,
                'totalQuantity' => (int) $dateData->totalQuantity
            ]);
        }

        return response()->json(['data' => $dates->values()]);
    }
     /**
     * Provides a summary of statistics for the current day,
     * scoped to the currently authenticated user if they are not an admin.
     */
    public function todaySummary(Request $request)
    {
        // Authorization check removed
        $user = Auth::user();
        $today = Carbon::today();

        // --- Base Query for Today's Orders ---
        // If user is not an admin, scope queries to only their recorded orders/payments
        $ordersQuery = Order::whereDate('created_at', $today);
        if (!$user->hasRole('admin')) {
            $ordersQuery->where('user_id', $user->id);
        }

        // --- Status Counts ---
        $statusCounts = (clone $ordersQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // --- Payment/Income Summary ---
        // We query the payments table directly for accuracy, based on orders created today
        $paymentsQuery = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereDate('payments.payment_date', $today) // Payments recorded today
            ->where('payments.type', 'payment'); // Only count payments, not refunds

        if (!$user->hasRole('admin')) {
            // Scope payments to those recorded by the current user OR for orders created by them
            $paymentsQuery->where(function($q) use ($user) {
                $q->where('payments.user_id', $user->id)
                  ->orWhere('orders.user_id', $user->id);
            });
        }
        
        $paymentBreakdown = (clone $paymentsQuery)
            ->select('method', DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->pluck('total', 'method');

        $totalIncome = $paymentBreakdown->sum();

        return response()->json([
            'data' => [
                'status_counts' => [
                    'pending' => $statusCounts->get('pending', 0),
                    'processing' => $statusCounts->get('processing', 0),
                    'delivered' => $statusCounts->get('delivered', 0),
                    'completed' => $statusCounts->get('completed', 0),
                    'cancelled' => $statusCounts->get('cancelled', 0),
                ],
                'income_summary' => [
                    'total' => (float) $totalIncome,
                    'cash' => (float) $paymentBreakdown->get('cash', 0),
                    'card' => (float) $paymentBreakdown->get('card', 0), // Or combine card-like methods
                    'online' => (float) $paymentBreakdown->get('online', 0),
                    'bank' => (float) $paymentBreakdown->get('bank_transfer', 0),
                ]
            ]
        ]);
    }
}