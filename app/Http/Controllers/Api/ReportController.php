<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Purchase;
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
    /**
     * Generates a cost summary report for a given date range.
     * This combines data from both Expenses and Purchases.
     */
    public function costSummary(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : Carbon::now()->endOfDay();
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : $dateTo->copy()->subDays(29)->startOfDay();

        // --- 1. Get total expenses in the period ---
        $totalExpenses = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])->sum('amount');
        
        // --- 2. Get total purchases in the period ---
        // We consider purchases 'paid' or 'received' as costs incurred in this period.
        $totalPurchases = Purchase::whereIn('status', ['received', 'paid', 'partially_paid'])
                                  ->whereBetween('purchase_date', [$dateFrom, $dateTo])
                                  ->sum('total_amount');

        // --- 3. Get breakdown of expenses by category ---
        $expensesByCategory = Expense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select(DB::raw('COALESCE(expense_categories.name, "بدون فئة") as category'), DB::raw('SUM(expenses.amount) as total_amount'))
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get();
        
        // --- 4. Get breakdown of purchases by supplier ---
        $purchasesBySupplier = Purchase::whereIn('status', ['received', 'paid', 'partially_paid'])
            ->whereBetween('purchase_date', [$dateFrom, $dateTo])
            ->with('supplier:id,name') // Eager load supplier name
            ->select('supplier_id', DB::raw('SUM(total_amount) as total_amount'))
            ->groupBy('supplier_id')
            ->orderBy('total_amount', 'desc')
            ->limit(10) // Top 10 suppliers
            ->get();
        
        // The relationship won't be loaded automatically from the grouped query, so we map it
        $purchasesBySupplier->transform(fn($item) => [
            'name' => $item->supplier?->name ?? 'Unknown Supplier',
            'total_amount' => (float) $item->total_amount
        ]);

        return response()->json([
            'data' => [
                'summary' => [
                    'total_expenses' => (float) $totalExpenses,
                    'total_purchases' => (float) $totalPurchases,
                    'total_cost' => (float) $totalExpenses + (float) $totalPurchases,
                ],
                'expenses_by_category' => $expensesByCategory,
                'purchases_by_supplier' => $purchasesBySupplier,
                'date_range' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString(),
                ]
            ]
        ]);
    }
      /**
     * Fetches orders that are ready for pickup but have passed their due date.
     */
    public function overduePickupOrders(Request $request)
    {
        $this->authorize('report:view-operational'); // استخدم صلاحية جديدة للتقارير التشغيلية

        $query = Order::with('customer:id,name,phone')
            ->whereNotNull('pickup_date') // يجب أن يكون لها تاريخ استحقاق
            ->whereRaw('Date(pickup_date) < ?', [Carbon::today()]); // تاريخ الاستحقاق في الماضي

        // إضافة فلتر لعدد أيام التأخير
        if ($request->filled('overdue_days') && is_numeric($request->overdue_days)) {
            $overdueDays = (int) $request->overdue_days;
            $cutoffDate = Carbon::today()->subDays($overdueDays);
            $query->whereRaw('Date(pickup_date) <= ?', [$cutoffDate]);
        }

        // حساب عدد أيام التأخير مباشرة في الاستعلام
        $query->select('*', DB::raw('DATEDIFF(NOW(), pickup_date) as overdue_days'));

        $orders = $query->orderBy('pickup_date', 'asc')->paginate($request->get('per_page', 20));

        // لا نحتاج إلى Resource هنا لأننا أضفنا حقلًا مخصصًا
        // لكن استخدامه يضمن التناسق. سنقوم بتعديل OrderResource.
        return OrderResource::collection($orders);
    }
}