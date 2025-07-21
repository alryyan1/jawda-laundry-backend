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
use Illuminate\Support\Facades\Log;
use App\Pdf\OrdersReportPdf;

class ReportController extends Controller
{
    public function __construct()
    {
        // Protect all report methods with a specific permission.
        // You can get more granular later (e.g., report:view-financial vs. report:view-operational).
        // Exclude PDF methods from permission checks to allow public access
        $this->middleware('can:report:view-financial')->except(['exportOrdersReportPdf', 'viewOrdersReportPdf']);
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
            'month' => 'nullable|date_format:Y-m', // Format: 2024-01
            'top_services_limit' => 'nullable|integer|min:1|max:20',
        ]);

        // Handle monthly view
        if (isset($validated['month'])) {
            $monthDate = Carbon::createFromFormat('Y-m', $validated['month']);
            $dateFrom = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()->startOfDay();
            $dateTo = Carbon::createFromFormat('Y-m', $validated['month'])->endOfMonth()->endOfDay();
        } else {
            // Set default date range to the last 30 days if not provided
            $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to'])->endOfDay() : Carbon::now()->endOfDay();
            $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from'])->startOfDay() : $dateTo->copy()->subDays(29)->startOfDay();
        }
        
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

        // --- 2. Daily Breakdown (for monthly view) ---
        $dailyBreakdown = [];
        if (isset($validated['month'])) {
            $dailyBreakdown = DB::table('orders')
                ->where('status', 'completed')
                ->whereBetween('updated_at', [$dateFrom, $dateTo])
                ->select(
                    DB::raw('DATE(updated_at) as date'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue')
                )
                ->groupBy(DB::raw('DATE(updated_at)'))
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($day) {
                    return [
                        'date' => $day->date,
                        'total_orders' => (int) $day->total_orders,
                        'total_revenue' => (float) $day->total_revenue,
                    ];
                });
        }

        // --- 3. Top Performing Services Calculation ---
        $topServices = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('service_offerings', 'order_items.service_offering_id', '=', 'service_offerings.id')
            ->join('product_types', 'service_offerings.product_type_id', '=', 'product_types.id')
            ->join('service_actions', 'service_offerings.service_action_id', '=', 'service_actions.id')
            // ->where('orders.status', 'completed')
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
            ->get()
            ->map(function ($service) {
                return [
                    'id' => (int) $service->id,
                    'display_name' => $service->display_name,
                    'total_quantity' => (int) $service->total_quantity,
                    'total_revenue' => (float) $service->total_revenue,
                ];
            });

        // --- 4. Return the consolidated report data ---
        return response()->json([
            'data' => [
                'summary' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders' => (int) $totalOrders,
                    'average_order_value' => (float) $averageOrderValue,
                ],
                'daily_breakdown' => $dailyBreakdown,
                'top_services' => $topServices,
                'date_range' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString(),
                ],
                'view_type' => isset($validated['month']) ? 'monthly' : 'custom_range'
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

    
    /**
     * Generates a daily revenue and order count report for a specific month.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dailyRevenueReport(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . date('Y'),
        ]);

        $year = $validated['year'];
        $month = $validated['month'];
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Query to get aggregated data for completed orders, grouped by date
        $dailyData = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate]) // Use updated_at for completion date
            ->select(
                DB::raw('DATE(updated_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as daily_revenue')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date'); // Key by date for easy lookup

        // --- Create a full calendar for the month ---
        $reportData = [];
        $totalDaysInMonth = $startDate->daysInMonth;
        $totalMonthRevenue = 0;
        $totalMonthOrders = 0;

        for ($day = 1; $day <= $totalDaysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day)->format('Y-m-d');
            
            if (isset($dailyData[$currentDate])) {
                $dayData = $dailyData[$currentDate];
                $reportData[] = [
                    'date' => $dayData->date,
                    'order_count' => (int) $dayData->order_count,
                    'daily_revenue' => (float) $dayData->daily_revenue,
                ];
                $totalMonthRevenue += $dayData->daily_revenue;
                $totalMonthOrders += $dayData->order_count;
            } else {
                // Add an entry with 0 values for days with no sales
                $reportData[] = [
                    'date' => $currentDate,
                    'order_count' => 0,
                    'daily_revenue' => 0,
                ];
            }
        }

        return response()->json([
            'data' => [
                'report_details' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => $startDate->format('F Y'),
                ],
                'summary' => [
                    'total_revenue' => (float) $totalMonthRevenue,
                    'total_orders' => (int) $totalMonthOrders,
                    'average_daily_revenue' => $totalDaysInMonth > 0 ? (float)($totalMonthRevenue / $totalDaysInMonth) : 0,
                ],
                'daily_data' => $reportData,
            ]
        ]);
    }


    
    /**
     * Generates a daily cost and expense count report for a specific month.
     */
    public function dailyCostsReport(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . date('Y'),
        ]);

        $year = $validated['year'];
        $month = $validated['month'];
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // --- Query for Expenses ---
        $dailyData = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(expense_date) as date'),
                DB::raw('COUNT(*) as expense_count'),
                DB::raw('SUM(amount) as daily_cost')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        // --- Create a full calendar for the month ---
        $reportData = [];
        $totalMonthCost = 0;
        $totalMonthEntries = 0;
        $totalDaysInMonth = $startDate->daysInMonth;

        for ($day = 1; $day <= $totalDaysInMonth; $day++) {
            $currentDate = Carbon::create($year, $month, $day)->format('Y-m-d');
            
            if (isset($dailyData[$currentDate])) {
                $dayData = $dailyData[$currentDate];
                $reportData[] = [
                    'date' => $dayData->date,
                    'expense_count' => (int) $dayData->expense_count,
                    'daily_cost' => (float) $dayData->daily_cost,
                ];
                $totalMonthCost += $dayData->daily_cost;
                $totalMonthEntries += $dayData->expense_count;
            } else {
                $reportData[] = [
                    'date' => $currentDate,
                    'expense_count' => 0,
                    'daily_cost' => 0,
                ];
            }
        }

        return response()->json([
            'data' => [
                'report_details' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => $startDate->format('F Y'),
                ],
                'summary' => [
                    'total_cost' => (float) $totalMonthCost,
                    'total_entries' => (int) $totalMonthEntries,
                    'average_daily_cost' => $totalDaysInMonth > 0 ? (float)($totalMonthCost / $totalDaysInMonth) : 0,
                ],
                'daily_data' => $reportData,
            ]
        ]);
    }

    /**
     * Get orders report data
     */
    public function getOrdersReport(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        try {
            $orders = Order::with([
                'customer',
                'user',
                'items.serviceOffering.productType.category',
                'items.serviceOffering.serviceAction',
                'payments'
            ])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('order_date', 'desc')
            ->get();

            // Calculate summary statistics
            $totalOrders = $orders->count();
            $totalAmount = $orders->sum('paid_amount');
            $averageOrderValue = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

            return response()->json([
                'orders' => OrderResource::collection($orders),
                'summary' => [
                    'total_orders' => $totalOrders,
                    'total_amount' => $totalAmount,
                    'average_order_value' => $averageOrderValue,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating orders report: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate report', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export orders report as PDF
     */
    public function exportOrdersReportPdf(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        try {
            $orders = Order::with([
                'customer',
                'user',
                'items.serviceOffering.productType.category',
                'items.serviceOffering.serviceAction',
                'payments'
            ])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('order_date', 'desc')
            ->get();

            // Generate PDF
            $pdf = new OrdersReportPdf();
            $pdf->setOrders($orders);
            $pdf->setDateRange($dateFrom, $dateTo);
            $pdf->setSettings([
                'company_name' => config('app_settings.company_name', config('app.name')),
                'company_address' => config('app_settings.company_address'),
                'currency_symbol' => config('app_settings.currency_symbol', '$'),
            ]);

            $pdfContent = $pdf->generate();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="orders-report-' . $dateFrom . '-to-' . $dateTo . '.pdf"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting orders report PDF: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to export report'], 500);
        }
    }

    /**
     * View orders report as PDF (inline)
     */
    public function viewOrdersReportPdf(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        try {
            $orders = Order::with([
                'customer',
                'user',
                'items.serviceOffering.productType.category',
                'items.serviceOffering.serviceAction',
                'payments'
            ])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('order_date', 'desc')
            ->get();

            // Generate PDF
            $pdf = new OrdersReportPdf();
            $pdf->setOrders($orders);
            $pdf->setDateRange($dateFrom, $dateTo);
            $pdf->setSettings([
                'company_name' => config('app_settings.company_name', config('app.name')),
                'company_address' => config('app_settings.company_address'),
                'currency_symbol' => config('app_settings.currency_symbol', '$'),
            ]);

            $pdfContent = $pdf->generate();

            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="orders-report-' . $dateFrom . '-to-' . $dateTo . '.pdf"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error viewing orders report PDF: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to view report'], 500);
        }
    }
}