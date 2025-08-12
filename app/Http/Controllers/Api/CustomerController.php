<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct()
    {
        // Apply permissions middleware
        $this->middleware('can:customer:view')->only('show');
        $this->middleware('can:customer:create')->only('store');
        $this->middleware('can:customer:update')->only('update');
        $this->middleware('can:customer:delete')->only('destroy');
        $this->middleware('can:order:record-payment')->only('recordPayment');
    }

    /**
     * Display a listing of the customers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Customer::withCount('orders')
                         ->with('customerType') // Eager load customer type
                         ->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            });
        }

        if ($request->filled('customer_type_id')) {
            $query->where('customer_type_id', $request->customer_type_id);
        }

        $customers = $query->paginate($request->get('per_page', 10));
        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Http\Resources\CustomerResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30', // Max length for phone
            'address' => 'nullable|string|max:1000', // Max length for address
            'customer_type_id' => 'sometimes|nullable|exists:customer_types,id',
            'is_default' => 'sometimes|boolean',
        ]);

        try {
            // Assign the currently authenticated user (staff) as the creator if your model supports it
            $validatedData['user_id'] = Auth::id(); // Assuming 'user_id' links to staff

            if (isset($validatedData['is_default']) && $validatedData['is_default']) {
                // Unset default for all other customers
                Customer::where('is_default', true)->update(['is_default' => false]);
            }

            $customer = Customer::create($validatedData);

            return new CustomerResource($customer);
        } catch (\Exception $e) {
            Log::error("Error creating customer: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create customer. Please try again.'.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified customer.
     *
     * @param  \App\Models\Customer  $customer
     * @return \App\Http\Resources\CustomerResource
     */
    public function show(Customer $customer)
    {
        // Eager load related data if needed by CustomerResource
        // $customer->loadMissing('orders'); // Example if you want to show recent orders
        return new CustomerResource($customer);
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \App\Http\Resources\CustomerResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'address' => 'sometimes|nullable|string|max:1000',
            'customer_type_id' => 'sometimes|nullable|exists:customer_types,id',
            'is_default' => 'sometimes|boolean',
        ]);

        try {
            if (isset($validatedData['is_default']) && $validatedData['is_default']) {
                // Unset default for all other customers
                Customer::where('is_default', true)->where('id', '!=', $customer->id)->update(['is_default' => false]);
            }
            $customer->update($validatedData);
            return new CustomerResource($customer);
        } catch (\Exception $e) {
            Log::error("Error updating customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update customer. Please try again.' , 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Record a payment for a customer.
     * This creates a special "customer payment" order to handle customer-level payments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordPayment(Request $request, Customer $customer)
    {
        // Validate the payment data
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => ['required', 'string', Rule::in(['cash', 'card', 'bank_transfer', 'check', 'other'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Create a special "customer payment" order
            $order = Order::create([
                // Customer Payment - no order_number needed
                'daily_order_number' => 1,
                'customer_id' => $customer->id,
                'user_id' => Auth::id(),
                'status' => 'completed',
                'order_complete' => true,
                'order_type' => 'customer_payment',
                'total_amount' => 0, // This is a payment order, so total is 0
                'paid_amount' => $validated['amount'],
                'payment_status' => 'paid',
                'notes' => $validated['notes'] ?? 'Customer payment recorded',
                'order_date' => now(),
                'due_date' => now(),
            ]);

            // Create the payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'type' => 'payment',
                'transaction_id' => null,
                'notes' => $validated['notes'] ?? 'Customer payment',
                'payment_date' => now(),
            ]);

            DB::commit();

            Log::info('Customer payment recorded', [
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
            ]);

            return response()->json([
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $validated['amount'],
                    'method' => $validated['method'],
                    'notes' => $validated['notes'] ?? null,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error recording customer payment: " . $e->getMessage());
            return response()->json(['message' => 'Failed to record payment. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Customer $customer)
    {
        // Optional: Business logic before deletion
        // For example, prevent deletion if the customer has active/unpaid orders.
        // This is a design decision. For now, we'll allow deletion.
        // if ($customer->orders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
        //     return response()->json(['message' => 'Cannot delete customer with active orders. Please resolve orders first.'], 409); // 409 Conflict
        // }

        try {
            $customer->delete(); // This will perform a soft delete if the Customer model uses the SoftDeletes trait
            return response()->json(['message' => 'Customer deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete customer. Please try again.'], 500);
        }
    }
}