<?php
// app/Http/Controllers/Api/OrderController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service; // For fetching service price
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // For order_number

class OrderController extends Controller
{
    public function index()
    {
        // Add pagination, filtering, sorting later
        return OrderResource::collection(Order::with(['customer', 'user', 'items.service'])->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'sometimes|in:pending,processing,ready_for_pickup,completed,cancelled',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $orderTotal = 0;
        $orderItemsData = [];

        foreach ($validatedData['items'] as $item) {
            $service = Service::find($item['service_id']);
            if (!$service) {
                // This should be caught by 'exists' validation, but good to double check
                return response()->json(['message' => "Service with ID {$item['service_id']} not found."], 400);
            }
            $priceAtOrder = $service->price;
            $subTotal = $item['quantity'] * $priceAtOrder;
            $orderTotal += $subTotal;

            $orderItemsData[] = [
                'service_id' => $item['service_id'],
                'quantity' => $item['quantity'],
                'price_at_order' => $priceAtOrder,
                'sub_total' => $subTotal,
            ];
        }

        $order = Order::create([
            'order_number' => 'ORD-' . strtoupper(Str::random(8)), // Simple order number generation
            'customer_id' => $validatedData['customer_id'],
            'user_id' => Auth::id(), // Staff member who created it
            'status' => $validatedData['status'] ?? 'pending',
            'total_amount' => $orderTotal,
            'notes' => $validatedData['notes'] ?? null,
            'due_date' => $validatedData['due_date'] ?? null,
        ]);

        $order->items()->createMany($orderItemsData);
        $order->load(['customer', 'user', 'items.service']); // Eager load for response

        return new OrderResource($order);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'user', 'items.service']);
        return new OrderResource($order);
    }

    // Implement update and destroy similarly
}