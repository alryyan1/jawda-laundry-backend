<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Services\PricingService; // Assuming you have this
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // For transactions
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // For order_number generation

class OrderController extends Controller
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer:id,name', 'user:id,name'])->latest(); // Select specific columns for relations

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }


        $orders = $query->paginate($request->get('per_page', 10));
        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedOrderData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date_format:Y-m-d',
            'items' => 'required|array|min:1',
            'items.*.service_offering_id' => 'required|exists:service_offerings,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_description_custom' => 'nullable|string|max:255',
            'items.*.length_meters' => 'nullable|numeric|min:0|required_if:items.*.width_meters,!=,null', // required if width is present
            'items.*.width_meters' => 'nullable|numeric|min:0|required_if:items.*.length_meters,!=,null', // required if length is present
            'items.*.notes' => 'nullable|string|max:1000',
        ]);

        $customer = Customer::findOrFail($validatedOrderData['customer_id']);
        $orderTotalAmount = 0;
        $orderItemsToCreate = [];

        DB::beginTransaction();
        try {
            foreach ($validatedOrderData['items'] as $itemData) {
                $serviceOffering = ServiceOffering::findOrFail($itemData['service_offering_id']);
                $priceDetails = $this->pricingService->calculatePrice(
                    $serviceOffering,
                    $customer,
                    $itemData['quantity'],
                    $itemData['length_meters'] ?? null,
                    $itemData['width_meters'] ?? null
                );

                $orderItemsToCreate[] = [
                    // 'order_id' will be set after order creation if not using $order->items()->createMany()
                    'service_offering_id' => $serviceOffering->id,
                    'product_description_custom' => $itemData['product_description_custom'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'length_meters' => $itemData['length_meters'] ?? null,
                    'width_meters' => $itemData['width_meters'] ?? null,
                    'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                    'sub_total' => $priceDetails['sub_total'],
                    'notes' => $itemData['notes'] ?? null,
                ];
                $orderTotalAmount += $priceDetails['sub_total'];
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'status' => 'pending', // Default status
                'total_amount' => $orderTotalAmount,
                'paid_amount' => 0, // Default paid amount
                'payment_status' => 'pending', // Default payment status
                'notes' => $validatedOrderData['notes'] ?? null,
                'due_date' => $validatedOrderData['due_date'] ?? null,
                'order_date' => now(),
            ]);

            $order->items()->createMany($orderItemsToCreate);

            DB::commit();
            $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating order: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create order. Please try again. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer.customerType', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction']);
        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     * For item updates, this uses a delete-and-recreate strategy for simplicity.
     */
    public function update(Request $request, Order $order)
    {
        $validatedOrderData = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id', // Usually customer shouldn't change easily
            'status' => ['sometimes', 'required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])],
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date_format:Y-m-d',
            'payment_method' => 'nullable|string|max:50',
            'payment_status' => ['nullable', Rule::in(['pending', 'paid', 'partially_paid', 'refunded'])],
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'sometimes|array', // Items array is optional
            'items.*.service_offering_id' => 'required_with:items|exists:service_offerings,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.product_description_custom' => 'nullable|string|max:255',
            'items.*.length_meters' => 'nullable|numeric|min:0|required_if:items.*.width_meters,!=,null',
            'items.*.width_meters' => 'nullable|numeric|min:0|required_if:items.*.length_meters,!=,null',
            'items.*.notes' => 'nullable|string|max:1000',
            // If an item has an 'id', it's an existing item (not used in delete-recreate but could be for selective update)
            // 'items.*.id' => 'nullable|integer|exists:order_items,id,order_id,' . $order->id,
        ]);

        DB::beginTransaction();
        try {
            // Update order-level details
            $order->fill($request->only(['notes', 'due_date', 'payment_method', 'payment_status'])); // Only specific fields

            if ($request->has('status')) {
                $this->updateOrderStatusLogic($order, $request->status);
            }

            if ($request->has('paid_amount')) {
                // Ensure paid_amount doesn't exceed total_amount if total_amount is not also changing
                $newPaidAmount = (float) $request->paid_amount;
                $currentTotal = $order->total_amount;
                if ($request->has('items')) { // If items are changing, total will be recalculated
                    // Defer this check until after items are processed
                } else if ($newPaidAmount > $currentTotal) {
                    return response()->json(['message' => 'Paid amount cannot exceed total amount.'], 422);
                }
                $order->paid_amount = $newPaidAmount;
            }


            // If items are provided, delete old items and create new ones
            if ($request->has('items')) {
                $customer = Customer::findOrFail($order->customer_id); // Customer doesn't change on order edit typically
                $newTotalAmount = 0;
                $newOrderItemsData = [];

                foreach ($validatedOrderData['items'] as $itemData) {
                    $serviceOffering = ServiceOffering::findOrFail($itemData['service_offering_id']);
                    $priceDetails = $this->pricingService->calculatePrice(
                        $serviceOffering,
                        $customer,
                        $itemData['quantity'],
                        $itemData['length_meters'] ?? null,
                        $itemData['width_meters'] ?? null
                    );
                    $newOrderItemsData[] = [
                        'service_offering_id' => $serviceOffering->id,
                        'product_description_custom' => $itemData['product_description_custom'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'length_meters' => $itemData['length_meters'] ?? null,
                        'width_meters' => $itemData['width_meters'] ?? null,
                        'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                        'sub_total' => $priceDetails['sub_total'],
                        'notes' => $itemData['notes'] ?? null,
                    ];
                    $newTotalAmount += $priceDetails['sub_total'];
                }
                $order->total_amount = $newTotalAmount; // Update total amount based on new items
                $order->items()->delete(); // Delete existing items
                $order->items()->createMany($newOrderItemsData); // Create new items
            }

            // Re-check paid amount if total amount was updated
            if ($request->has('items') && $order->paid_amount > $order->total_amount) {
                // This scenario needs careful handling - e.g. auto-adjust payment_status, or error
                // For now, let's cap paid_amount to new total_amount if it exceeds and was not explicitly set in this request.
                if (!$request->has('paid_amount')) {
                    $order->paid_amount = $order->total_amount;
                } else if ((float)$request->paid_amount > $order->total_amount) {
                    DB::rollBack(); // Or handle differently
                    return response()->json(['message' => 'New paid amount cannot exceed new total amount.'], 422);
                }
            }

            // Update payment status based on amounts
            if ($order->paid_amount >= $order->total_amount && $order->total_amount > 0) {
                $order->payment_status = 'paid';
            } elseif ($order->paid_amount > 0 && $order->paid_amount < $order->total_amount) {
                $order->payment_status = 'partially_paid';
            } elseif ($order->paid_amount == 0 && $order->total_amount > 0) {
                $order->payment_status = 'pending';
            }


            $order->save();
            DB::commit();

            $order->load(['customer.customerType', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order {$order->id}: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to update order. ' . $e->getMessage()], 500);
        }
    }


    /**
     * Update only the status of the specified order.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])],
        ]);

        DB::beginTransaction();
        try {
            $this->updateOrderStatusLogic($order, $validated['status']);
            $order->save();
            DB::commit();

            $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order status for {$order->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update order status.'], 500);
        }
    }

    /**
     * Helper function for status update logic.
     */
    private function updateOrderStatusLogic(Order $order, string $newStatus): void
    {
        // Prevent reverting from completed/cancelled without specific logic
        if (($order->status === 'completed' || $order->status === 'cancelled') && $order->status !== $newStatus) {
            // Potentially throw an exception or handle this case as a business rule violation
            // For now, we allow it, but in a real app, you might restrict this.
        }

        $order->status = $newStatus;
        if ($newStatus === 'completed' && !$order->pickup_date) {
            $order->pickup_date = now();
            // If also fully paid, ensure payment_status is 'paid'
            if ($order->paid_amount >= $order->total_amount) {
                $order->payment_status = 'paid';
            }
        } elseif ($newStatus === 'ready_for_pickup') {
            // Potentially send notification to customer
        } elseif ($newStatus === 'cancelled') {
            // Potentially handle refunds, stock adjustments, etc.
        }
    }

    /**
     * Record a payment for the specified order.
     */
    public function recordPayment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'amount_paid' => 'required|numeric|min:0.01', // Must pay something
            'payment_method' => 'required|string|max:50',
            'payment_date' => 'nullable|date_format:Y-m-d', // Frontend should send Y-m-d
            'transaction_id' => 'nullable|string|max:255', // For card/online payments
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json(['message' => 'Cannot record payment for a completed or cancelled order.'], 400);
        }
        if ($order->paid_amount >= $order->total_amount) {
            return response()->json(['message' => 'Order is already fully paid.'], 400);
        }

        DB::beginTransaction();
        try {
            $newlyPaidAmount = (float) $validated['amount_paid'];
            $order->paid_amount += $newlyPaidAmount;

            // Cap paid amount at total amount
            if ($order->paid_amount > $order->total_amount) {
                $order->paid_amount = $order->total_amount;
            }

            if ($order->paid_amount >= $order->total_amount) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'partially_paid';
            }

            // If not set by general update, set payment method from this specific payment
            if (empty($order->payment_method) || $order->payment_method === 'pending') {
                $order->payment_method = $validated['payment_method'];
            }


            // Optionally create a separate Payment record/log
            // $order->payments()->create([
            //     'amount' => $newlyPaidAmount,
            //     'payment_method' => $validated['payment_method'],
            //     'payment_date' => $validated['payment_date'] ? Carbon::parse($validated['payment_date']) : now(),
            //     'transaction_id' => $validated['transaction_id'] ?? null,
            //     'notes' => $validated['notes'] ?? null,
            //     'user_id' => Auth::id(), // Staff who recorded payment
            // ]);

            $order->save();
            DB::commit();

            $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);
            return new OrderResource($order);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error recording payment for order {$order->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to record payment.'], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // Generally, orders are cancelled or archived, not hard deleted.
        // If hard delete is needed:
        if ($order->status !== 'cancelled' && $order->status !== 'pending') { // Example restriction
            return response()->json(['message' => 'Only pending or cancelled orders can be deleted.'], 400);
        }
        DB::beginTransaction();
        try {
            $order->items()->delete(); // Delete related items first if no cascade on delete for them
            $order->delete(); // This will soft delete if trait is used
            DB::commit();
            return response()->json(['message' => 'Order deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting order {$order->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete order.'], 500);
        }
    }


    /**
     * Quote an order item.
     */
    public function quoteOrderItem(Request $request)
    {
        // ... (as defined previously)
        $validatedData = $request->validate([
            'service_offering_id' => 'required|exists:service_offerings,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity' => 'required|integer|min:1',
            'length_meters' => 'nullable|numeric|min:0',
            'width_meters' => 'nullable|numeric|min:0',
        ]);

        try {
            $serviceOffering = ServiceOffering::findOrFail($validatedData['service_offering_id']);
            $customer = Customer::findOrFail($validatedData['customer_id']);

            $priceDetails = $this->pricingService->calculatePrice(
                $serviceOffering,
                $customer,
                $validatedData['quantity'],
                $validatedData['length_meters'] ?? null,
                $validatedData['width_meters'] ?? null
            );

            return response()->json([
                'calculated_price_per_unit_item' => $priceDetails['calculated_price_per_unit_item'],
                'sub_total' => $priceDetails['sub_total'],
                'applied_unit' => $priceDetails['applied_unit'],
                'strategy_applied' => $priceDetails['strategy_applied'],
                'message' => 'Quote calculated successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Invalid service offering or customer ID provided.'], 404);
        } catch (\Exception $e) {
            Log::error("Error quoting order item: " . $e->getMessage());
            return response()->json(['message' => 'Failed to calculate price quote.'], 500);
        }
    }
}
