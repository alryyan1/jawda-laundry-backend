<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ServiceOffering;
use App\Models\DiningTable;
use App\Models\CustomerProductServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Pdf\InvoicePdf;
use App\Models\Setting;
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
use App\Models\PrintJob;
use App\Events\PrintJobCreated;

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

        // Authorization middleware removed
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
        // Check if this is an empty order creation (flag or no items provided)
        $isEmptyOrder = (
            ($request->has('create_empty_order') && $request->boolean('create_empty_order') === true)
            || !$request->has('items')
            || empty($request->input('items'))
        );
        
        // Normalize empty string customer_id to null so it passes nullable validation
        if ($request->has('customer_id') && $request->input('customer_id') === '') {
            $request->merge(['customer_id' => null]);
        }
        
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
            // Make customer optional; backend will assign default customer if missing
            $validationRules['customer_id'] = 'nullable|exists:customers,id';
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
        
        // If no customer provided, assign the system default customer if available
        if (!$customer) {
            $defaultCustomer = Customer::where('is_default', true)->first();
            if ($defaultCustomer) {
                $customer = $defaultCustomer;
            }
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
        $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'payments', 'diningTable']);
        return new OrderResource($order);
    }

    /**
     * Return items for a specific order (for independent cart fetching)
     */
    public function getOrderItems(Order $order)
    {
        // Authorization check removed

        $items = $order->items()
            ->with(['serviceOffering.productType.category', 'serviceOffering.serviceAction'])
            ->get();

        return response()->json([
            'order_id' => $order->id,
            'items' => $items,
        ]);
    }
      /**
     * Update the specified resource in storage.
     * This now handles updates for notes, due_date, status, pickup_date, and adding items.
     */
    public function update(Request $request, Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        // Authorization check removed

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
            'status' => ['sometimes', 'required', Rule::in(['pending', 'processing', 'delivered', 'completed', 'cancelled'])],
            'order_complete' => 'sometimes|boolean',
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

        // Handle order_complete based on status changes
        if ($oldStatus !== 'completed' && $newStatus === 'completed') {
            $order->order_complete = true;
            $order->completed_at = now();
            Log::info('Setting order_complete to true for order:', ['order_id' => $order->id]);
        } elseif ($newStatus === 'cancelled') {
            $order->order_complete = false;
            Log::info('Setting order_complete to false for cancelled order:', ['order_id' => $order->id]);
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

            // Pickup date is fixed on creation and should not change afterwards

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
        // Authorization check removed
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'delivered', 'completed', 'cancelled'])],
            'delivered_date' => ['nullable', 'date']
        ]);
        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        $warnings = []; // Array to collect warnings

        if ($oldStatus !== $newStatus) {
            DB::beginTransaction();
            try {
                $order->status = $newStatus;
                if ($newStatus === 'completed') {
                    // Do not change pickup_date here; it is fixed on creation
                    $order->order_complete = true;
                    if (!$order->completed_at) $order->completed_at = now();
                } elseif ($newStatus === 'delivered') {
                    // Set delivered_date to current date if not provided
                    if (!$order->delivered_date) {
                        $order->delivered_date = $validated['delivered_date'] ?? now();
                    }
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
     * Mark order as received and recalculate total amount from order items.
     */
    public function markOrderReceived(Request $request, Order $order)
    {
        // Authorization check removed

        // Check if order is already received
        if ($order->received) {
            return response()->json([
                'message' => 'Order is already marked as received.',
                'order' => new OrderResource($order)
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Log the initial state
            $oldTotal = $order->total_amount;
            $calculatedTotal = $order->calculated_total_amount;
            
            Log::info('Marking order as received - initial state:', [
                'order_id' => $order->id,
                'old_total_amount' => $oldTotal,
                'calculated_total_amount' => $calculatedTotal,
                'items_count' => $order->items()->count(),
            ]);
            
            // Set received to true and set received_at timestamp
            $order->received = true;
            $order->received_at = now();
            
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
            Log::info('Order marked as received - final state:', [
                'order_id' => $order->id,
                'new_total_amount' => $order->total_amount,
                'received' => $order->received,
                'received_at' => $order->received_at,
            ]);
            
            $order->logActivity("Order marked as received. Total amount recalculated: " . $order->total_amount);
            
            DB::commit();

            // Refresh the order to get the latest data from database
            $order->refresh();

            // Load relationships for response
            $order->load(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable']);

            // Broadcast the order updated event
            event(new OrderUpdated($order, ['received' => true, 'received_at' => $order->received_at]));
            Log::info('Order marked as received', ['order_id' => $order->id]);

            // Auto-send receive order message if enabled
            $this->sendReceiveOrderMessage($order);

            return response()->json([
                'message' => 'Order marked as received successfully.',
                'order' => new OrderResource($order)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error marking order as received: " . $e->getMessage());
            return response()->json(['message' => 'Failed to mark order as received. An internal error occurred.'], 500);
        }
    }

    /**
     * Cancel a received order by setting received to false.
     */
    public function cancelOrder(Order $order, NotifyCustomerForOrderStatus $notifier)
    {
        // Authorization check removed

        // Check if order is received (can only cancel received orders)
        if (!$order->received) {
            return response()->json([
                'message' => 'Only received orders can be cancelled.',
                'order' => new OrderResource($order)
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Set received to false and clear received_at timestamp
            $order->received = false;
            $order->received_at = null;
            // $order->status = 'cancelled';
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
            'customer_id' => 'nullable|exists:customers,id',
            'quantity' => 'required|integer|min:1',
            'length_meters' => 'nullable|numeric|min:0',
            'width_meters' => 'nullable|numeric|min:0',
            'order_item_id' => 'nullable|exists:order_items,id', // Optional: if provided, update the order item
        ]);

        try {
            $serviceOffering = ServiceOffering::findOrFail($validatedData['service_offering_id']);
            $customer = isset($validatedData['customer_id']) ? Customer::findOrFail($validatedData['customer_id']) : null;
            
            // If order_item_id is provided, update the order item dimensions in the database
            if (isset($validatedData['order_item_id'])) {
                $orderItem = OrderItem::findOrFail($validatedData['order_item_id']);
                $orderItem->length_meters = $validatedData['length_meters'] ?? null;
                $orderItem->width_meters = $validatedData['width_meters'] ?? null;
                $orderItem->save();
                
                Log::info('Updated order item dimensions:', [
                    'order_item_id' => $orderItem->id,
                    'length_meters' => $orderItem->length_meters,
                    'width_meters' => $orderItem->width_meters,
                ]);
            }
            
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
     * Update order item dimensions
     */
    public function updateOrderItemDimensions(Request $request, OrderItem $orderItem)
    {
        // Authorization check removed

        $validatedData = $request->validate([
            'length_meters' => 'nullable|numeric|min:0',
            'width_meters' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            
            // Update the order item dimensions
            $orderItem->length_meters = $validatedData['length_meters'] ?? null;
            $orderItem->width_meters = $validatedData['width_meters'] ?? null;
            $orderItem->save();
            
            // Recalculate the order item's subtotal using the new dimensions and quantity
            $pricingService = app(PricingService::class);
            $priceDetails = $pricingService->calculatePrice(
                $orderItem->serviceOffering,
                $orderItem->order->customer,
                $orderItem->quantity,
                $orderItem->length_meters,
                $orderItem->width_meters
            );
            
            // Update the order item's calculated price and subtotal
            $orderItem->calculated_price_per_unit_item = $priceDetails['calculated_price_per_unit_item'];
            $orderItem->sub_total = $priceDetails['sub_total'];
            $orderItem->save();
            
            Log::info('Updated order item dimensions with quantity consideration:', [
                'order_item_id' => $orderItem->id,
                'quantity' => $orderItem->quantity,
                'length_meters' => $orderItem->length_meters,
                'width_meters' => $orderItem->width_meters,
                'calculated_price_per_unit' => $priceDetails['calculated_price_per_unit_item'],
                'subtotal' => $priceDetails['sub_total'],
                'product_type' => $orderItem->serviceOffering->productType->name,
            ]);
            
            // Recalculate the order's total amount
            $orderItem->order->recalculateTotalAmount();
            
            // Refresh the order to ensure we have the latest data
            $orderItem->order->refresh();
            
            // Regenerate category sequences to reflect any changes
            $orderItem->order->generateCategorySequences(true);
            
            Log::info('Updated order item dimensions and recalculated totals:', [
                'order_item_id' => $orderItem->id,
                'order_id' => $orderItem->order->id,
                'length_meters' => $orderItem->length_meters,
                'width_meters' => $orderItem->width_meters,
                'new_subtotal' => $orderItem->sub_total,
                'new_order_total' => $orderItem->order->total_amount,
                'category_sequences' => $orderItem->order->category_sequences,
            ]);
            
            DB::commit();
            
            // Load relationships for response
            $orderItem->load(['serviceOffering.productType', 'serviceOffering.serviceAction']);
            
            return response()->json([
                'message' => 'Order item dimensions updated successfully.',
                'order_item' => $orderItem,
                'order_total' => $orderItem->order->total_amount,
                'category_sequences' => $orderItem->order->category_sequences,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order item dimensions: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update order item dimensions.'], 500);
        }
    }

    /**
     * Update order item quantity
     */
    public function updateOrderItemQuantity(Request $request, OrderItem $orderItem)
    {
        // Authorization check removed

        $validatedData = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            
            // Update the order item quantity
            $orderItem->quantity = $validatedData['quantity'];
            $orderItem->save();
            
            // Recalculate the order item's subtotal using the new quantity and existing dimensions
            $pricingService = app(PricingService::class);
            $priceDetails = $pricingService->calculatePrice(
                $orderItem->serviceOffering,
                $orderItem->order->customer,
                $orderItem->quantity,
                $orderItem->length_meters,
                $orderItem->width_meters
            );
            
            // Update the order item's calculated price and subtotal
            $orderItem->calculated_price_per_unit_item = $priceDetails['calculated_price_per_unit_item'];
            $orderItem->sub_total = $priceDetails['sub_total'];
            $orderItem->save();
            
            Log::info('Updated order item quantity with recalculation:', [
                'order_item_id' => $orderItem->id,
                'quantity' => $orderItem->quantity,
                'length_meters' => $orderItem->length_meters,
                'width_meters' => $orderItem->width_meters,
                'calculated_price_per_unit' => $priceDetails['calculated_price_per_unit_item'],
                'subtotal' => $priceDetails['sub_total'],
                'product_type' => $orderItem->serviceOffering->productType->name,
            ]);
            
            // Recalculate the order's total amount
            $orderItem->order->recalculateTotalAmount();
            
            // Refresh the order to ensure we have the latest data
            $orderItem->order->refresh();
            
            // Regenerate category sequences to reflect the new quantity
            $orderItem->order->generateCategorySequences(true);
            
            Log::info('Updated order item quantity and recalculated totals:', [
                'order_item_id' => $orderItem->id,
                'order_id' => $orderItem->order->id,
                'quantity' => $orderItem->quantity,
                'new_subtotal' => $orderItem->sub_total,
                'new_order_total' => $orderItem->order->total_amount,
                'category_sequences' => $orderItem->order->category_sequences,
            ]);
            
            DB::commit();
            
            // Load relationships for response
            $orderItem->load(['serviceOffering.productType', 'serviceOffering.serviceAction']);
            
            return response()->json([
                'message' => 'Order item quantity updated successfully.',
                'order_item' => $orderItem,
                'order_total' => $orderItem->order->total_amount,
                'category_sequences' => $orderItem->order->category_sequences,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order item quantity: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update order item quantity.'], 500);
        }
    }
    
    /**
     * Update order item notes
     */
    public function updateOrderItemNotes(Request $request, OrderItem $orderItem)
    {
        // Authorization check removed

        $validatedData = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();
            
            // Update the order item notes
            $orderItem->notes = $validatedData['notes'] ?? null;
            $orderItem->save();
            
            Log::info('Updated order item notes:', [
                'order_item_id' => $orderItem->id,
                'notes' => $orderItem->notes,
                'product_type' => $orderItem->serviceOffering->productType->name,
            ]);
            
            // Load relationships for response
            $orderItem->load(['serviceOffering.productType', 'serviceOffering.serviceAction']);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Order item notes updated successfully.',
                'order_item' => $orderItem,
                'order' => $orderItem->order,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating order item notes: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update order item notes.'], 500);
        }
    }
    
    /**
     * Generate and download a PDF invoice for the specified order using raw TCPDF methods.
     */
    public function downloadInvoice(Order $order)
    {
        // $this->authorize('view', $order);

        // Eager load all necessary data for the invoice
        $order->load(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        // Create PDF with professional header/footer and branding
        $pdf = new InvoicePdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setOrder($order);
        $pdf->setCompanyDetails(
            $this->getSetting('company_name', config('app.name')),
            $this->getSetting('company_address')
        );
        $pdf->setSettings([
            'general_company_name' => $this->getSetting('company_name', config('app.name')),
            'general_company_name_ar' => $this->getSetting('company_name_ar', ''),
            'general_company_address' => $this->getSetting('company_address'),
            'general_company_address_ar' => $this->getSetting('company_address_ar', ''),
            'general_company_phone' => $this->getSetting('company_phone'),
            'general_company_phone_2' => $this->getSetting('company_phone_2'),
            'company_logo_url' => $this->getSetting('company_logo_url'),
            'general_default_currency_symbol' => $this->getSetting('currency_symbol', 'OMR'),
            'language' => $this->getSetting('pdf_language', 'en'),
        ]);

        // Meta
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor(config('app.name'));
        $pdf->SetTitle('Invoice ' . $order->id);
        $pdf->SetSubject('Order Invoice');

        // Layout
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 45, 10);
        $pdf->SetHeaderMargin(6);
        $pdf->SetFooterMargin(12);
        $pdf->SetFont('arial', '', 10);

        // Build PDF
        $pdf->generate();

        // Stream to browser
        $pdf->Output('invoice-' . $order->id . '.pdf', 'I');
        exit;
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
        // No need to assemble settings; PDF will load directly from DB
        $pdf->setSettings([]);
        
        // Calculate the actual height needed including category headers
        $requiredHeight = $pdf->calculateTotalHeight();
        $pageHeight = max(120, $requiredHeight + 20 + 20); // Minimum 120mm, add 20mm buffer
        
        // Recreate PDF with calculated height
        $pdf = new PosInvoicePdf('P', 'mm', [80, $pageHeight], true, 'UTF-8', false);
        $pdf->setOrder($order);
        $pdf->setSettings([]);

        // Set document information
        $pdf->SetTitle('Receipt ' . $order->id);
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
            return $pdf->Output('receipt-'.$order->id.'.pdf', 'S');
        } else {
            // Close and output PDF document
            // 'I' for inline browser display. This is best for POS printing.
            // The browser's PDF viewer will handle the print dialog.
            $pdf->Output('receipt-'.$order->id.'.pdf', 'I');
            exit;
        }
    }

    /**
     * Return POS invoice PDF as base64 for client-side silent printing bridges
     */
    public function downloadPosInvoiceBase64(Order $order)
    {
        $content = $this->downloadPosInvoice($order, true);
        return response()->json([
            'order_id' => $order->id,
            'pdf_base64' => base64_encode($content),
            'filename' => 'receipt-'.$order->id.'.pdf'
        ]);
    }

    /**
     * Enqueue a print job for the POS invoice and broadcast to printer agent(s)
     */
    public function enqueuePrintJob(Order $order)
    {
        // Create a print job record
        $printJob = PrintJob::create([
            'order_id' => $order->id,
            'status' => 'queued',
            'attempts' => 0,
        ]);

        // Broadcast event to any listening agent(s)
        event(new PrintJobCreated($printJob));

        return response()->json([
            'message' => 'Print job queued',
            'job_id' => $printJob->id,
            'order_id' => $order->id,
            'pdf_url' => url("/api/orders/{$order->id}/pos-invoice-pdf"),
        ], 202);
    }

    /**
     * Update print job status (called by agent)
     */
    public function updatePrintJobStatus(Request $request, PrintJob $printJob)
    {
        $validated = $request->validate([
            'status' => 'required|in:processing,printed,failed',
            'error_message' => 'nullable|string'
        ]);
        $printJob->status = $validated['status'];
        if ($validated['status'] === 'failed') {
            $printJob->attempts = $printJob->attempts + 1;
            $printJob->error_message = $validated['error_message'] ?? null;
        }
        $printJob->save();

        return response()->json(['message' => 'Print job updated']);
    }
    
    /**
     * Generates an invoice PDF and sends it via WhatsApp using UltraMsg API.
     */
    public function sendWhatsappInvoice(Order $order, WhatsAppService $whatsAppService)
    {
        // Authorization check removed

        if (!$whatsAppService->isConfigured()) {
            return response()->json(['message' => 'WhatsApp API is not configured.'], 400);
        }

        $customer = $order->customer;
        if (!$customer || !$customer->phone) {
            return response()->json(['message' => 'Customer phone number is missing.'], 400);
        }

        try {
            // --- 1. Generate the PDF using the refactored method ---
            $pdfContent = $this->downloadPosInvoice($order, true); // true for base64

            // --- 2. Base64 Encode the PDF Content for UltraMsg ---
            $base64Pdf = base64_encode($pdfContent);
            $fileName = 'Invoice-' . $order->id . '.pdf';
            $caption = "Hello {$customer->name}, here is the invoice for your order #{$order->id}. Thank you!";

            // --- 3. Send via WhatsApp Service using UltraMsg document API with base64 ---
            // Create data URL for UltraMsg
            $dataUrl = "data:application/pdf;base64,{$base64Pdf}";
            
            // Use the customer's phone number directly - WhatsAppService will format it
            $result = $whatsAppService->sendMedia($customer->phone, $dataUrl, $fileName, $caption);

            // --- 4. Return Response to Frontend ---
            if ($result['status'] === 'success') {
                // Update the tracking field
                $order->update(['whatsapp_pdf_sent' => true]);
                // Optionally log this action
                $order->logActivity("Invoice sent to customer via WhatsApp (UltraMsg).");
                return response()->json(['message' => 'Invoice sent successfully via WhatsApp!']);
            } else {
                Log::error("WhatsApp invoice sending failed", [
                    'order_id' => $order->id,
                    'customer_phone' => $customer->phone,
                    'result' => $result
                ]);
                
                return response()->json([
                    'message' => 'Failed to send WhatsApp invoice.',
                    'details' => $result['message'] ?? 'Unknown API error.',
                    'api_response' => $result['data'] ?? null
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error("Exception in sendWhatsappInvoice", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to send WhatsApp invoice.',
                'details' => 'Exception: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sends a custom WhatsApp message to the customer about their order.
     */
    public function sendWhatsappMessage(Request $request, Order $order, WhatsAppService $whatsAppService)
    {
        // Authorization check removed

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

        // Use the customer's phone number directly - WhatsAppService will format it
        $result = $whatsAppService->sendMessage($customer->phone, $validated['message']);

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
        // Reuse the same query builder logic from the index method
        $query = $this->buildOrderQuery($request);
        
        // Get all matching orders without pagination for the export
        $orders = $query->with([
            'customer',
            'items.serviceOffering.productType.category',
            'items.serviceOffering.serviceAction',
            'payments'
        ])->get();
        
        try {
            // Use the professional Excel export
            $excelExport = new \App\Excel\OrdersExcelExport();
            $excelExport->setOrders($orders);
            $excelExport->setFilters($request->all());
            $excelExport->setSettings([
                'company_name' => \app_setting('company_name', config('app.name')),
                'company_address' => \app_setting('company_address'),
                'currency_symbol' => \app_setting('currency_symbol', 'OMR'),
            ]);

            $excelContent = $excelExport->generate();
            
            $fileName = 'orders_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return response($excelContent, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting orders Excel: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to export Excel file'], 500);
        }
    }
    
    /**
     * Get all orders for a specific date without pagination (for TodayOrdersColumn).
     */
    public function getTodayOrders(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = $request->get('date');
        
        $query = Order::with([
            'customer:id,name,phone', 
            'items.serviceOffering.productType.category', 
            'items.serviceOffering.serviceAction', 
            'diningTable'
        ])->orderBy('id', 'desc');

        if ($date) {
            // Use specific date
            $query->whereDate('created_at', $date);
        } else {
            // Use today's date
            $query->whereDate('created_at', now()->toDateString());
        }

        // Get all orders without pagination
        $orders = $query->get();

        // Disable wrapping and return as a simple array
        \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();
        return response()->json($orders);
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

        // Search by order ID (exact match)
        if ($request->filled('order_id')) {
            $query->where('id', $request->order_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(fn($q) => $q->where('id', $searchTerm)
                ->orWhere('id', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'LIKE', "%{$searchTerm}%")));
        }

        // Search specifically in category sequences
        if ($request->filled('category_sequence_search')) {
            $searchTerm = $request->category_sequence_search;
            // Use JSON_EXTRACT and LIKE for partial matching within JSON values (works with MariaDB)
            $query->whereRaw("JSON_EXTRACT(category_sequences, '$.*') LIKE ?", ['%' . $searchTerm . '%']);
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

        // Filter for incomplete orders only (where completed_at is null)
        if ($request->boolean('show_only_incomplete')) {
            $query->whereNull('completed_at');
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

    private function getSetting(string $key, $default = null)
    {
        try {
            if (function_exists('app_setting')) {
                return \app_setting($key, $default);
            }
        } catch (\Throwable $e) {
            // ignore and fallback to config
        }
        $fallbackMap = [
            'company_name' => config('app.name'),
            'company_address' => '',
            'company_phone' => '',
            'company_phone_2' => '',
            'company_phone_ar' => '',
            'company_logo_url' => '',
            'currency_symbol' => 'OMR',
            'pdf_language' => 'en',
            'company_name_ar' => '',
            'company_address_ar' => '',
        ];
        return $fallbackMap[$key] ?? $default;
    }
    
    /**
     * Get the height breakdown for a POS invoice (useful for debugging)
     */
    public function getPosInvoiceHeight(Order $order)
    {
        // Authorization check removed
        
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);
        
        $pdf = new PosInvoicePdf('P', 'mm', [80, 297], true, 'UTF-8', false);
        $pdf->setOrder($order);
        $settings = [
            'general_company_name' => \app_setting('company_name', config('app.name')),
            'general_company_name_ar' => \app_setting('company_name_ar', ''),
            'general_company_address' => \app_setting('company_address'),
            'general_company_address_ar' => \app_setting('company_address_ar', 'مسقط'),
            'general_company_phone' => \app_setting('company_phone'),
            'general_company_phone_ar' => \app_setting('company_phone_ar', '--'),
            'general_default_currency_symbol' => \app_setting('currency_symbol', 'OMR'),
            'company_logo_url' => \app_setting('company_logo_url'),
            'language' => 'en',
        ];
        $pdf->setSettings($settings);
        
        $heightBreakdown = $pdf->getHeightBreakdown();
        $totalHeight = $pdf->calculateTotalHeight();
        
        return response()->json([
            'order_id' => $order->id,
            'id' => $order->id,
            'total_height_mm' => $totalHeight,
            'height_breakdown' => $heightBreakdown,
            'items_count' => $order->items->count(),
            'categories_count' => $order->items->groupBy('serviceOffering.productType.category.id')->count(),
            'recommended_page_height_mm' => max(120, $totalHeight + 20)
        ]);
    }

    /**
     * Send receive order message via WhatsApp if enabled in settings
     */
    private function sendReceiveOrderMessage(Order $order)
    {
        try {
            // Check if customer has phone number
            if (!$order->customer || !$order->customer->phone) {
                Log::info("Cannot send receive order message: Customer has no phone number", [
                    'order_id' => $order->id,
                    'customer_id' => $order->customer?->id
                ]);
                return;
            }

            // Check if auto-send receive order message is enabled
            $settingsService = app(\App\Services\SettingsService::class);
            $autoSendReceiveMessage = $settingsService->get('pos_auto_send_receive_order_message', false);
            
            if (!$autoSendReceiveMessage) {
                Log::info("Auto-send receive order message is disabled", ['order_id' => $order->id]);
                return;
            }

            // Get company name for the message
            $companyName = \app_setting('company_name', config('app.name'));
            
            // Create the receive order message
            $message = "🎉 *Order Received Successfully!*\n\n";
            $message .= "Dear *{$order->customer->name}*,\n\n";
            $message .= "Thank you for choosing *{$companyName}*! We have successfully received your order.\n\n";
            $message .= "📋 *Order Details:*\n";
            $message .= "• Order #: *{$order->id}*\n";
            $message .= "• Received Date: *" . now()->format('d/m/Y H:i') . "*\n";
            $message .= "• Total Items: *" . $order->items->sum('quantity') . "*\n";
            $message .= "• Total Amount: *" . number_format($order->total_amount, 3) . " OMR*\n\n";
            $message .= "🔄 *What's Next:*\n";
            $message .= "We will start processing your order immediately. You will receive updates on the progress.\n\n";
            $message .= "📞 *Contact Us:*\n";
            $message .= "If you have any questions, please don't hesitate to contact us.\n\n";
            $message .= "Thank you for your trust in our services! 🙏";

            // Send the message via WhatsApp
            $whatsappService = app(\App\Services\WhatsAppService::class);
            $result = $whatsappService->sendMessage($order->customer->phone, $message);

            if ($result['status'] === 'success') {
                // Update order to mark that receive message was sent
                $order->order_receive_message_sent = true;
                $order->save();
                
                Log::info("Receive order message sent successfully", [
                    'order_id' => $order->id,
                    'customer_phone' => $order->customer->phone,
                    'result' => $result
                ]);
            } else {
                Log::error("Failed to send receive order message", [
                    'order_id' => $order->id,
                    'customer_phone' => $order->customer->phone,
                    'error' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error sending receive order message", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update the order type for a specific order
     */
    public function updateOrderType(Request $request, Order $order)
    {
        // Authorization check removed
        
        $validatedData = $request->validate([
            'order_type' => 'required|in:in_house,take_away,delivery',
        ]);

        $oldOrderType = $order->order_type;
        $order->order_type = $validatedData['order_type'];
        $order->save();

        // Log the order type change
        Log::info('Order type updated', [
            'order_id' => $order->id,
            'old_order_type' => $oldOrderType,
            'new_order_type' => $order->order_type,
            'user_id' => Auth::id(),
        ]);

        // Load the order with relationships for the response
        $order->load(['customer', 'user', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction']);

        return response()->json([
            'message' => 'Order type updated successfully',
            'order' => new OrderResource($order),
        ]);
    }

 
}