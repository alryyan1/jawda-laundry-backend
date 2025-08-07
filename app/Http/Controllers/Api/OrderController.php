<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\ServiceOffering;
use App\Models\DiningTable;
use App\Models\CustomerProductServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Pdf\InvoicePdf;
use App\Pdf\PosInvoicePdf;
use App\Services\PricingService; // <-- Import the service
use App\Services\WhatsAppService;
use App\Actions\NotifyCustomerForOrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    protected PricingService $pricingService;
    // protected InventoryService $inventoryService; // Removed inventory service dependency

    public function __construct(PricingService $pricingService)
    {
        // Use Laravel's service container to automatically inject the PricingService
        $this->pricingService = $pricingService;
        // Remove inventory service dependency
        // $this->inventoryService = $inventoryService;

        // Apply Spatie permissions middleware
        $this->middleware('can:order:list')->only('index');
        $this->middleware('can:order:view')->only('show');
        $this->middleware('can:order:create')->only(['store', 'quoteOrderItem']);
        $this->middleware('can:order:update')->only('update');
        $this->middleware('can:order:update-status')->only('updateStatus');
        $this->middleware('can:order:record-payment')->only('recordPayment');
        $this->middleware('can:order:delete')->only('destroy');
    }

     // Refactor the index method to use the helper
     public function index(Request $request)
     {
         $query = $this->buildOrderQuery($request);
         $orders = $query->paginate($request->get('per_page', 15));
         return OrderResource::collection($orders);
     }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if this is an empty order creation
        $isEmptyOrder = $request->has('create_empty_order') && $request->input('create_empty_order') === true;
        
        $validationRules = [
            'notes' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date_format:Y-m-d',
            'order_type' => 'sometimes|in:in_house,take_away,delivery',
            'dining_table_id' => 'nullable|exists:dining_tables,id',
        ];
        
        if ($isEmptyOrder) {
            // For empty orders, customer_id and items are optional
            $validationRules['customer_id'] = 'nullable|exists:customers,id';
            $validationRules['items'] = 'nullable|array';
        } else {
            // For regular orders, customer_id and items are required
            $validationRules['customer_id'] = 'required|exists:customers,id';
            $validationRules['items'] = 'required|array|min:1';
        }
        
        // Add item validation rules if items are provided
        if (!$isEmptyOrder || ($request->has('items') && !empty($request->input('items')))) {
            $validationRules['items.*.service_offering_id'] = 'required|integer';
            $validationRules['items.*.quantity'] = 'required|integer|min:1';
            $validationRules['items.*.product_description_custom'] = 'nullable|string|max:255';
            $validationRules['items.*.length_meters'] = 'nullable|numeric|min:0';
            $validationRules['items.*.width_meters'] = 'nullable|numeric|min:0';
            $validationRules['items.*.notes'] = 'nullable|string|max:1000';
        }
        
        $validatedData = $request->validate($validationRules);

        $customer = null;
        if (!empty($validatedData['customer_id'])) {
            $customer = Customer::findOrFail($validatedData['customer_id']);
        }
        
        $orderTotalAmount = 0;
        $orderItemsToCreate = [];
        $warnings = []; // Array to collect warnings

        DB::beginTransaction();
        try {
            // Only process items if they exist and are not empty
            if (!empty($validatedData['items'])) {
                foreach ($validatedData['items'] as $itemData) {
                    // Try to find the service offering in regular service_offerings table first
                    $serviceOffering = ServiceOffering::find($itemData['service_offering_id']);
                    
                    // If not found in regular table, try customer_product_service_offerings table
                    if (!$serviceOffering && $customer) {
                        $customerServiceOffering = CustomerProductServiceOffering::where('id', $itemData['service_offering_id'])
                            ->where('customer_id', $customer->id)
                            ->first();
                        
                        if ($customerServiceOffering) {
                            // Check if a regular service offering exists for this product_type and service_action
                            $regularServiceOffering = ServiceOffering::where('product_type_id', $customerServiceOffering->product_type_id)
                                ->where('service_action_id', $customerServiceOffering->service_action_id)
                                ->first();
                            
                            if (!$regularServiceOffering) {
                                // Create the missing regular service offering
                                $regularServiceOffering = ServiceOffering::create([
                                    'product_type_id' => $customerServiceOffering->product_type_id,
                                    'service_action_id' => $customerServiceOffering->service_action_id,
                                    'name' => $customerServiceOffering->name_override ?: $customerServiceOffering->serviceAction->name,
                                    'description' => $customerServiceOffering->description_override ?: $customerServiceOffering->serviceAction->description,
                                    'default_price' => $customerServiceOffering->default_price,
                                    'default_price_per_sq_meter' => $customerServiceOffering->default_price_per_sq_meter,
                                    'is_active' => $customerServiceOffering->is_active,
                                ]);
                            }
                            
                            // Use the regular service offering for the order item
                            $serviceOffering = $regularServiceOffering;
                        }
                    }
                    
                    if (!$serviceOffering) {
                        throw new \Exception("Service offering not found for ID: " . $itemData['service_offering_id']);
                    }

                    $priceDetails = $this->pricingService->calculatePrice(
                        $serviceOffering,
                        $customer,
                        $itemData['quantity'],
                        $itemData['length_meters'] ?? null,
                        $itemData['width_meters'] ?? null
                    );

                    $orderItemsToCreate[] = [
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
            }

            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                'customer_id' => $customer ? $customer->id : null,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'order_type' => $validatedData['order_type'] ?? 'in_house',
                'dining_table_id' => $validatedData['dining_table_id'] ?? null, // Add dining table ID
                'total_amount' => $orderTotalAmount,
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'notes' => $validatedData['notes'] ?? null,
                'due_date' => $validatedData['due_date'] ?? null,
                'pickup_date' => now()->addDays(3), // Set pickup date to 3 days after order creation
                'order_date' => now(),
            ]);

            $order->items()->createMany($orderItemsToCreate);
            
            // Recalculate total amount from created items to ensure consistency
            $order->recalculateTotalAmount();
            
            // Update dining table status to occupied if the order has a dining table
            if ($order->dining_table_id) {
                $diningTable = DiningTable::find($order->dining_table_id);
                if ($diningTable) {
                    $diningTable->update(['status' => 'occupied']);
                }
            }
            
            // Generate category sequences for the order (inside transaction)
            if ($order->items()->count() > 0) {
                $order->generateCategorySequences();
            }
            
            DB::commit();

            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);
            
            // Broadcast the order created event
            event(new OrderCreated($order));
            Log::info('OrderCreated event fired', ['order_id' => $order->id]);
            
            // Return order with warnings if any
            $response = new OrderResource($order);
            if (!empty($warnings)) {
                return response()->json([
                    'order' => $response,
                    'warnings' => $warnings,
                    'message' => 'Order created successfully with some warnings.'
                ]);
            }
            
            return response()->json([
                'order' => $response,
                'message' => 'Order created successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating order: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to create order. An internal error occurred.' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer.customerType', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'payments', 'diningTable']);
        return new OrderResource($order);
    }
      /**
     * Update the specified resource in storage.
     * This now handles updates for notes, due_date, status, pickup_date, and adding items.
     */
    public function update(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('update', $order);

        // Debug: Log the incoming request data
        Log::info('Order update request data:', [
            'order_id' => $order->id,
            'request_data' => $request->all(),
            'status' => $request->input('status'),
            'order_complete' => $request->input('order_complete'),
        ]);

        $validatedData = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'notes' => 'sometimes|nullable|string|max:2000',
            'due_date' => 'sometimes|nullable|date_format:Y-m-d',
            'status' => ['sometimes', 'required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])],
            'order_complete' => 'sometimes|boolean',
            'pickup_date' => 'sometimes|nullable|date_format:Y-m-d H:i:s', // Expects a full datetime string from frontend
            'order_type' => 'sometimes|in:in_house,take_away,delivery',
            'items' => 'sometimes|array|min:1', // Allow items to be updated
            'items.*.service_offering_id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.product_description_custom' => 'nullable|string|max:255',
            'items.*.length_meters' => 'nullable|numeric|min:0',
            'items.*.width_meters' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:1000',
        ]);
        
        $oldStatus = $order->status;
        $order->fill($validatedData); // Fill all validated data
        $newStatus = $order->status;
        $warnings = []; // Array to collect warnings

        // Debug: Log the status changes and order_complete field
        Log::info('Order status and order_complete debug:', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_order_complete' => $order->getOriginal('order_complete'),
            'new_order_complete' => $order->order_complete,
            'validated_order_complete' => $validatedData['order_complete'] ?? null,
        ]);

        // If status changed to 'completed' and no pickup date was explicitly provided, set it to now.
        if ($oldStatus !== 'completed' && $newStatus === 'completed' && !$request->has('pickup_date')) {
            $order->pickup_date = now();
        }
        
        // Handle order_complete based on status changes
        if ($oldStatus !== 'completed' && $newStatus === 'completed') {
            $order->order_complete = true;
            Log::info('Setting order_complete to true for order:', ['order_id' => $order->id]);
        } elseif ($newStatus === 'cancelled') {
            $order->order_complete = false;
            Log::info('Setting order_complete to false for cancelled order:', ['order_id' => $order->id]);
        }
        
        // If status changed to something else, clear the pickup date unless it was explicitly sent.
        if ($newStatus !== 'completed' && !$request->has('pickup_date')) {
             $order->pickup_date = null;
        }

        DB::beginTransaction();
        try {
            // Handle items update if provided
            if ($request->has('items')) {
                $customer = $order->customer;
                
                // If no customer is set, we can't calculate prices
                if (!$customer) {
                    return response()->json([
                        'message' => 'Cannot add items to order without a customer. Please select a customer first.'
                    ], 400);
                }
                
                $orderTotalAmount = 0;
                $orderItemsToCreate = [];

                // Process all items (existing + new)
                foreach ($validatedData['items'] as $itemData) {
                    // Try to find the service offering in regular service_offerings table first
                    $serviceOffering = ServiceOffering::find($itemData['service_offering_id']);
                    
                    // If not found in regular table, try customer_product_service_offerings table
                    if (!$serviceOffering) {
                        $customerServiceOffering = CustomerProductServiceOffering::where('id', $itemData['service_offering_id'])
                            ->where('customer_id', $customer->id)
                            ->first();
                        
                        if ($customerServiceOffering) {
                            // Create a temporary service offering object from customer data
                            $serviceOffering = new ServiceOffering();
                            $serviceOffering->id = $customerServiceOffering->id;
                            $serviceOffering->product_type_id = $customerServiceOffering->product_type_id;
                            $serviceOffering->service_action_id = $customerServiceOffering->service_action_id;
                            $serviceOffering->name = $customerServiceOffering->name_override ?: $customerServiceOffering->serviceAction->name;
                            $serviceOffering->description = $customerServiceOffering->description_override ?: $customerServiceOffering->serviceAction->description;
                            $serviceOffering->default_price = $customerServiceOffering->custom_price ?: $customerServiceOffering->default_price;
                            $serviceOffering->default_price_per_sq_meter = $customerServiceOffering->custom_price_per_sq_meter ?: $customerServiceOffering->default_price_per_sq_meter;
                            $serviceOffering->is_active = $customerServiceOffering->is_active;
                            
                            // Load the relationships
                            $serviceOffering->productType = $customerServiceOffering->productType;
                            $serviceOffering->serviceAction = $customerServiceOffering->serviceAction;
                        }
                    }
                    
                    if (!$serviceOffering) {
                        throw new \Exception("Service offering not found for ID: " . $itemData['service_offering_id']);
                    }

                    $priceDetails = $this->pricingService->calculatePrice(
                        $serviceOffering,
                        $customer,
                        $itemData['quantity'],
                        $itemData['length_meters'] ?? null,
                        $itemData['width_meters'] ?? null
                    );

                    $orderItemsToCreate[] = [
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

                // Delete existing items and create new ones
                $order->items()->delete();
                $order->items()->createMany($orderItemsToCreate);
                
                // Recalculate and update order total from items
                $order->recalculateTotalAmount();
                
                $order->logActivity("Order items were updated. New total: " . $order->total_amount);
            }

            // Save all changes
            $order->save();
            
            // Check if order_complete was set to true and recalculate total amount
            $oldOrderComplete = $order->getOriginal('order_complete');
            if (!$oldOrderComplete && $order->order_complete) {
                $order->recalculateTotalAmountWithItemRecalculation();
                $order->logActivity("Order marked as complete - total amount recalculated: " . $order->total_amount);
            }
            
            // Debug: Log the final state after save
            Log::info('Order saved - final state:', [
                'order_id' => $order->id,
                'status' => $order->status,
                'order_complete' => $order->order_complete,
                'pickup_date' => $order->pickup_date,
            ]);

            // If status changed, log it and send notification
            if ($oldStatus !== $newStatus) {
                // Remove all payments when order is cancelled
                if ($newStatus === 'cancelled') {
                    $paymentsCount = $order->payments()->count();
                    if ($paymentsCount > 0) {
                        $order->payments()->delete();
                        $order->paid_amount = 0;
                        $order->payment_status = 'pending';
                        $order->logActivity("Order cancelled - {$paymentsCount} payment(s) removed.");
                    }
                }
                
      
                
                // Remove inventory transaction creation when order is completed
                // if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                //     $order->load(['items.serviceOffering.productType']);
                //     $this->createInventoryTransactionsForOrder($order);
                // }
                
                $order->logActivity("Status changed from '{$oldStatus}' to '{$newStatus}'.");
                $notifier->execute($order);
            }

            if ($request->has('pickup_date')) {
                 $order->logActivity("Pickup date was updated.");
            }

            DB::commit();

            // Return the fresh resource with all relations
            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);
            
            // Generate category sequences for the order if items were updated
            if ($request->has('items') && $order->items()->count() > 0) {
                $order->generateCategorySequences(true); // true for update
            }
            
            // Broadcast the order updated event
            event(new OrderUpdated($order, ['status' => $newStatus]));
            Log::info('OrderUpdated event fired', ['order_id' => $order->id, 'new_status' => $newStatus]);
            
            // Return order with warnings if any
            $response = new OrderResource($order);
            if (!empty($warnings)) {
                return response()->json([
                    'order' => $response,
                    'warnings' => $warnings,
                    'message' => 'Order updated successfully with some warnings.'
                ]);
            }
            
            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to update order. An internal error occurred.' . $e->getMessage()], 500);
        }
    }
    /**
     * Update only the status of the specified order and trigger notifications.
     */
    public function updateStatus(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('updateStatus', $order);
        $validated = $request->validate(['status' => ['required', Rule::in(['pending', 'processing', 'ready_for_pickup', 'completed', 'cancelled'])]]);
        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        $warnings = []; // Array to collect warnings

        if ($oldStatus !== $newStatus) {
            DB::beginTransaction();
            try {
                $order->status = $newStatus;
                if ($newStatus === 'completed') {
                    if (!$order->pickup_date) $order->pickup_date = now();
                    $order->order_complete = true;
                } elseif ($newStatus === 'cancelled') {
                    $order->order_complete = false;
                }
                
                // Remove all payments when order is cancelled
                if ($newStatus === 'cancelled') {
                    $paymentsCount = $order->payments()->count();
                    if ($paymentsCount > 0) {
                        $order->payments()->delete();
                        $order->paid_amount = 0;
                        $order->payment_status = 'pending';
                        $order->logActivity("Order cancelled - {$paymentsCount} payment(s) removed.");
                    }
                }
                
                $order->save();
                
                // Update dining table status to available if order is completed and has a dining table
                if ($newStatus === 'completed' && $order->dining_table_id) {
                    $diningTable = DiningTable::find($order->dining_table_id);
                    if ($diningTable) {
                        $diningTable->update(['status' => 'available']);
                    }
                }
                
                // Update dining table status to available if order is cancelled and has a dining table
                if ($newStatus === 'cancelled' && $order->dining_table_id) {
                    $diningTable = DiningTable::find($order->dining_table_id);
                    if ($diningTable) {
                        $diningTable->update(['status' => 'available']);
                    }
                }
                
                // Remove inventory transaction creation when order is completed
                // if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                //     $order->load(['items.serviceOffering.productType']);
                //     $this->createInventoryTransactionsForOrder($order);
                // }
                
                $order->logActivity("Status changed from '{$oldStatus}' to '{$newStatus}'.");
                $notifier->execute($order); // Call the action to handle notification logic
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error updating order status: " . $e->getMessage());
                return response()->json(['message' => 'Failed to update order status.'], 500);
            }
        }
        
        $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'payments', 'diningTable']);
        
        // Broadcast the order updated event
        event(new OrderUpdated($order, ['status' => $newStatus]));
        
        // Return order with warnings if any
        $response = new OrderResource($order);
        if (!empty($warnings)) {
            return response()->json([
                'order' => $response,
                'warnings' => $warnings,
                'message' => 'Order status updated successfully with some warnings.'
            ]);
        }
        
        return $response;
    }

    /**
     * Mark order as complete without changing status or generating sequences.
     */
    public function markOrderComplete(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        // Check if order is already completed
        if ($order->order_complete) {
            return response()->json([
                'message' => 'Order is already marked as complete.',
                'order' => new OrderResource($order)
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Log the initial state
            $oldTotal = $order->total_amount;
            $calculatedTotal = $order->calculated_total_amount;
            
            Log::info('Marking order as complete - initial state:', [
                'order_id' => $order->id,
                'old_total_amount' => $oldTotal,
                'calculated_total_amount' => $calculatedTotal,
                'items_count' => $order->items()->count(),
            ]);
            
            // Only set order_complete to true, don't change status or pickup_date
            $order->order_complete = true;
            
            // Always recalculate total amount from order items with their current quantities, widths, and lengths
            $order->recalculateTotalAmountWithItemRecalculation();
            
            Log::info('Recalculated total amount from order items:', [
                'order_id' => $order->id,
                'new_total_amount' => $order->total_amount,
                'items_processed' => $order->items()->count(),
            ]);
            
            // Ensure the order is saved with the new values
            $order->save();
            
            // Log the final state
            Log::info('Order marked as complete - final state:', [
                'order_id' => $order->id,
                'new_total_amount' => $order->total_amount,
                'order_complete' => $order->order_complete,
            ]);
            
            $order->logActivity("Order marked as complete. Total amount recalculated: " . $order->total_amount);
            
            DB::commit();

            // Refresh the order to get the latest data from database
            $order->refresh();

            // Load relationships for response
            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);

            // Broadcast the order updated event
            event(new OrderUpdated($order, ['order_complete' => true]));
            Log::info('Order marked as complete', ['order_id' => $order->id]);

            return response()->json([
                'message' => 'Order marked as complete successfully.',
                'order' => new OrderResource($order)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error marking order as complete: " . $e->getMessage());
            return response()->json(['message' => 'Failed to mark order as complete. An internal error occurred.'], 500);
        }
    }

    /**
     * Cancel a completed order by setting order_complete to false.
     */
    public function cancelOrder(Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        $this->authorize('update', $order);

        // Check if order is completed (can only cancel completed orders)
        if (!$order->order_complete) {
            return response()->json([
                'message' => 'Only completed orders can be cancelled.',
                'order' => new OrderResource($order)
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Set order_complete to false and status to cancelled
            $order->order_complete = false;
            $order->status = 'cancelled';
            $order->pickup_date = null; // Clear pickup date for cancelled orders
            
            // Remove all payments when order is cancelled
            $paymentsCount = $order->payments()->count();
            if ($paymentsCount > 0) {
                $order->payments()->delete();
                $order->paid_amount = 0;
                $order->payment_status = 'pending';
                $order->logActivity("Order cancelled - {$paymentsCount} payment(s) removed.");
            }

            // Update dining table status to available if order has a dining table
            if ($order->dining_table_id) {
                $diningTable = DiningTable::find($order->dining_table_id);
                if ($diningTable) {
                    $diningTable->update(['status' => 'available']);
                }
            }

            $order->save();
            $order->logActivity("Order cancelled - order_complete set to false.");

            DB::commit();

            // Load relationships for response
            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);

            // Broadcast the order updated event
            event(new OrderUpdated($order, ['status' => 'cancelled']));
            Log::info('Order cancelled', ['order_id' => $order->id]);

            return response()->json([
                'message' => 'Order cancelled successfully.',
                'order' => new OrderResource($order)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error cancelling order: " . $e->getMessage());
            return response()->json(['message' => 'Failed to cancel order. An internal error occurred.'], 500);
        }
    }

    /**
     * Record a payment for the specified order.
     */
    public function recordPayment(Request $request, Order $order)
    {
        $validated = $request->validate(['amount_paid' => 'required|numeric|min:0.01']);

        if ($order->paid_amount >= $order->total_amount) {
            return response()->json(['message' => 'Order is already fully paid.'], 400);
        }

        $order->paid_amount = (float) $order->paid_amount + (float) $validated['amount_paid'];
        if ($order->paid_amount >= $order->total_amount) {
            $order->payment_status = 'paid';
            $order->paid_amount = $order->total_amount; // Cap payment at total
        } else {
            $order->payment_status = 'partially_paid';
        }
        $order->save();
        return new OrderResource($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // Orders are usually cancelled via status change, not deleted.
        // Using soft-deletes on the Order model is recommended.
        if ($order->status !== 'cancelled') {
            return response()->json(['message' => 'Only cancelled orders can be deleted.'], 400);
        }
        $order->delete(); // This will soft delete if the trait is used on the model.
        return response()->noContent();
    }

    /**
     * Quote a potential order item.
     */
    public function quoteOrderItem(Request $request)
    {
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
            return response()->json($priceDetails);
        } catch (\Exception $e) {
            Log::error("Error quoting order item: " . $e->getMessage());
            return response()->json(['message' => 'Failed to calculate price quote.'], 500);
        }
    }
    
    /**
     * Generate and download a PDF invoice for the specified order using raw TCPDF methods.
     */
    public function downloadInvoice(Order $order)
    {
        // $this->authorize('view', $order); // Check permission using Spatie/Policies

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // Instantiate your custom PDF class
        $pdf = new InvoicePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Pass data to the PDF class
        $pdf->setOrder($order);
        $pdf->setCompanyDetails(
            app_setting('company_name', config('app.name')),
            app_setting('company_address') . "\nPhone: " . app_setting('company_phone')
        );

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Invoice ' . $order->order_number);
        $pdf->SetSubject('Order Invoice');

        // Set default header/footer data (if not already set in your custom class)
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

        // Set margins
        $pdf->SetMargins(5, 38, 5); // Left, Top, Right
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);


        // Set font
        $pdf->SetFont('arial', '', 10);

        // Call your custom generate method which builds the PDF
        $pdf->generate();

        // Close and output PDF document
        // 'I' for inline browser display, 'D' for download
        $pdf->Output('invoice-'.$order->id.'.pdf', 'I');
        exit; // TCPDF's output can sometimes interfere with Laravel's response cycle. exit() is a safeguard.
    }
     /**
     * Generate and download a PDF invoice formatted for a POS thermal printer.
     */
    public function downloadPosInvoice(Order $order, bool $base64 = false)
    {
        // $this->authorize('view', $order);

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // --- PDF Generation ---
        // Page format arguments: orientation, unit, format (array(width, height) in mm for custom roll)
        // 80mm is a common POS paper width
        // Calculate dynamic page height based on content including category headers
        $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false); // Use A4 height as default
        
        // Pass data to the PDF class first so we can calculate height
        $pdf->setOrder($order);
        $settings = [
            'general_company_name' => app_setting('company_name', config('app.name')),
            'general_company_name_ar' => app_setting('company_name_ar', ''),
            'general_company_address' => app_setting('company_address'),
            'general_company_address_ar' => app_setting('company_address_ar', 'مسقط'),
            'general_company_phone' => app_setting('company_phone'),
            'general_company_phone_ar' => app_setting('company_phone_ar', '--'),
            'general_default_currency_symbol' => app_setting('currency_symbol', 'OMR'),
            'company_logo_url' => app_setting('company_logo_url'),
            'language' => 'en', // Default language, can be made configurable
        ];
        $pdf->setSettings($settings);
        
        // Calculate the actual height needed including category headers
        $requiredHeight = $pdf->calculateTotalHeight();
        $pageHeight = max(120, $requiredHeight + 20 + 20); // Minimum 120mm, add 20mm buffer
        
        // Recreate PDF with calculated height
        $pdf = new PosInvoicePdf('P', 'mm', [80, $pageHeight], true, 'UTF-8', false);
        $pdf->setOrder($order);
        $pdf->setSettings($settings);

        // Set document information
        $pdf->SetTitle('Receipt ' . $order->order_number);
        $pdf->SetAuthor(config('app.name'));
        $pdf->setPrintHeader(false); // We can control header in generate()
        $pdf->setPrintFooter(true);  // Use our custom footer

        // Set margins: left, top, right
        $pdf->SetMargins(4, 5, 4);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 15); // Margin from bottom

        // Call your custom generate method which builds the PDF with Cell()
        $pdf->generate();

        if ($base64) {
            // Return base64 encoded PDF content
            return $pdf->Output('receipt-'.$order->order_number.'.pdf', 'S');
        } else {
            // Close and output PDF document
            // 'I' for inline browser display. This is best for POS printing.
            // The browser's PDF viewer will handle the print dialog.
            $pdf->Output('receipt-'.$order->order_number.'.pdf', 'I');
            exit;
        }
    }
    
    /**
     * Generates an invoice PDF and sends it via WhatsApp.
     */
    public function sendWhatsappInvoice(Order $order, WhatsAppService $whatsAppService)
    {
        $this->authorize('view', $order); // Or a specific 'order:send-invoice' permission

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        // --- 1. Generate the PDF using the refactored method ---
        $pdfContent = $this->downloadPosInvoice($order, true); // true for base64

        // --- 2. Base64 Encode the PDF Content ---
        $base64Pdf = base64_encode($pdfContent);

        // --- 3. Send via WhatsApp Service ---
        $fileName = 'Invoice-' . $order->order_number . '.pdf';
        $caption = "Hello {$customer->name}, here is the invoice for your order #{$order->order_number}. Thank you!";
        
        // Sanitize phone number - remove '+', spaces, dashes
        $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone);

        $result = $whatsAppService->sendMediaBase64($phoneNumber, $base64Pdf, $fileName, $caption);

        // --- 4. Return Response to Frontend ---
        if ($result['status'] === 'success') {
            // Update the tracking field
            $order->update(['whatsapp_pdf_sent' => true]);
            // Optionally log this action
            $order->logActivity("Invoice sent to customer via WhatsApp.");
            return response()->json(['message' => 'Invoice sent successfully via WhatsApp!']);
        } else {
            return response()->json([
                'message' => 'Failed to send WhatsApp invoice.',
                'details' => $result['message'] ?? 'Unknown API error.',
                'api_response' => $result['data'] ?? null
            ], 500);
        }
    }

    /**
     * Sends a custom WhatsApp message to the customer about their order.
     */
    public function sendWhatsappMessage(Request $request, Order $order, WhatsAppService $whatsAppService)
    {
        $this->authorize('view', $order);

        $validated = $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        // Sanitize phone number - remove '+', spaces, dashes
        $phoneNumber = preg_replace('/[^0-9]/', '', $customer->phone);

        $result = $whatsAppService->sendMessage($phoneNumber, $validated['message']);

        if ($result['status'] === 'success') {
            // Update the tracking field
            $order->update(['whatsapp_text_sent' => true]);
            // Log this action
            $order->logActivity("Custom WhatsApp message sent to customer: " . substr($validated['message'], 0, 50) . "...");
            return response()->json(['message' => 'Message sent successfully via WhatsApp!']);
        } else {
            return response()->json([
                'message' => 'Failed to send WhatsApp message.',
                'details' => $result['message'] ?? 'Unknown API error.',
                'api_response' => $result['data'] ?? null
            ], 500);
        }
    }
     /**
     * Export a filtered list of orders to a CSV file.
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('order:list'); // Or a new 'report:export' permission

        // Reuse the same query builder logic from the index method
        $query = $this->buildOrderQuery($request);
        
        // Get all matching orders without pagination for the export
        $orders = $query->get();
        
        $fileName = 'orders_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');

            // Add Header Row
            fputcsv($file, [
                'ID', 'Order Number', 'Customer Name', 'Customer Phone', 'Status',
                'Order Date', 'Due Date', 'Pickup Date', 'Total Amount', 'Amount Paid', 'Amount Due'
            ]);

            // Add Data Rows
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->order_number,
                    $order->customer->name,
                    $order->customer->phone,
                    $order->status,
                    $order->order_date->format('Y-m-d H:i:s'),
                    $order->due_date ? $order->due_date->format('Y-m-d') : '',
                    $order->pickup_date ? $order->pickup_date->format('Y-m-d H:i:s') : '',
                    $order->total_amount,
                    $order->paid_amount,
                    $order->amount_due,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Helper function to build the order query based on request filters.
     * Reused by both index() and exportCsv().
     */
    private function buildOrderQuery(Request $request)
    {
        $query = Order::with(['customer:id,name,phone', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable'])->orderBy('id', 'desc');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('date_from')) $query->whereDate('order_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('order_date', '<=', $request->date_to);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(fn($q) => $q->where('id', $searchTerm)
                ->orWhere('order_number', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'LIKE', "%{$searchTerm}%")));
        }

        if ($request->filled('product_type_id')) {
            $query->whereHas('items.serviceOffering.productType', fn($q) => $q->where('id', $request->product_type_id));
        }
        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }
        
        // Handle today parameter
        if ($request->boolean('today')) {
            $query->whereDate('created_at', now()->toDateString());
        }
        return $query;
    }

    /**
     * Get order statistics for the specified date range.
     */
    public function statistics(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Order::query();

        if ($dateFrom) {
            $query->whereDate('order_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('order_date', '<=', $dateTo);
        }

        // Get total orders
        $totalOrders = $query->count();

        // Get total amount paid
        $totalAmountPaid = $query->sum('paid_amount');

        // Get payment breakdown with detailed information
        $paymentBreakdown = $this->calculatePaymentBreakdown($query, $totalAmountPaid);

        return response()->json([
            'totalOrders' => $totalOrders,
            'totalAmountPaid' => $totalAmountPaid,
            'paymentBreakdown' => $paymentBreakdown,
            'averagePerOrder' => $totalOrders > 0 ? round($totalAmountPaid / $totalOrders, 2) : 0,
        ]);
    }

    /**
     * Calculate detailed payment breakdown with percentages.
     */
    private function calculatePaymentBreakdown($query, $totalAmountPaid)
    {
        // Get payment breakdown from payments table
        $paymentData = $query->join('payments', 'orders.id', '=', 'payments.order_id')
            ->selectRaw('payments.method, SUM(payments.amount) as total_amount')
            ->groupBy('payments.method')
            ->get()
            ->keyBy('method')
            ->toArray();

        // Define all payment methods
        $allPaymentMethods = ['cash', 'visa', 'bank_transfer'];
        
        // Build detailed breakdown with percentages
        $detailedBreakdown = [];
        
        foreach ($allPaymentMethods as $method) {
            $amount = isset($paymentData[$method]) ? (float) $paymentData[$method]['total_amount'] : 0;
            $percentage = $totalAmountPaid > 0 ? round(($amount / $totalAmountPaid) * 100, 1) : 0;
            
            $detailedBreakdown[$method] = [
                'amount' => $amount,
                'percentage' => $percentage,
                'method' => $method
            ];
        }

        return $detailedBreakdown;
    }
    
    /**
     * Get the height breakdown for a POS invoice (useful for debugging)
     */
    public function getPosInvoiceHeight(Order $order)
    {
        $this->authorize('view', $order);
        
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);
        
        $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false);
        $pdf->setOrder($order);
        $settings = [
            'general_company_name' => app_setting('company_name', config('app.name')),
            'general_company_name_ar' => app_setting('company_name_ar', ''),
            'general_company_address' => app_setting('company_address'),
            'general_company_address_ar' => app_setting('company_address_ar', 'مسقط'),
            'general_company_phone' => app_setting('company_phone'),
            'general_company_phone_ar' => app_setting('company_phone_ar', '--'),
            'general_default_currency_symbol' => app_setting('currency_symbol', 'OMR'),
            'company_logo_url' => app_setting('company_logo_url'),
            'language' => 'en',
        ];
        $pdf->setSettings($settings);
        
        $heightBreakdown = $pdf->getHeightBreakdown();
        $totalHeight = $pdf->calculateTotalHeight();
        
        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total_height_mm' => $totalHeight,
            'height_breakdown' => $heightBreakdown,
            'items_count' => $order->items->count(),
            'categories_count' => $order->items->groupBy('serviceOffering.productType.category.id')->count(),
            'recommended_page_height_mm' => max(120, $totalHeight + 20)
        ]);
    }

 
}