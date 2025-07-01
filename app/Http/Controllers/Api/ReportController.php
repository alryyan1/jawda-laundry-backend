<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function salesSummary(Request $request)
    {
        $this->authorize('view-reports'); // Gate check

        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();

        $query = Order::where('status', 'completed')
                      ->whereBetween('updated_at', [$dateFrom, $dateTo]);

        $totalRevenue = $query->sum('total_amount');
        $totalOrders = $query->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $topServices = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('service_offerings', 'order_items.service_offering_id', '=', 'service_offerings.id')
            ->where('orders.status', 'completed')
            ->whereBetween('orders.updated_at', [$dateFrom, $dateTo])
            ->select(
                'service_offerings.id',
                DB::raw("COALESCE(service_offerings.name_override, CONCAT(pt.name, ' - ', sa.name)) as display_name"),
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.sub_total) as total_revenue')
            )
            ->join('product_types as pt', 'service_offerings.product_type_id', '=', 'pt.id')
            ->join('service_actions as sa', 'service_offerings.service_action_id', '=', 'sa.id')
            ->groupBy('service_offerings.id', 'display_name')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_revenue' => (float) $totalRevenue,
            'total_orders' => (int) $totalOrders,
            'average_order_value' => (float) $averageOrderValue,
            'top_services' => $topServices,
            'date_range' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
            ]
        ]);
    }
}