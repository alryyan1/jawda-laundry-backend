<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
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
            'email' => 'nullable|string|email|max:255|unique:customers,email',
            'phone' => 'nullable|string|max:30', // Max length for phone
            'address' => 'nullable|string|max:1000', // Max length for address
        ]);

        try {
            // Assign the currently authenticated user (staff) as the creator if your model supports it
            // $validatedData['user_id'] = Auth::id(); // Assuming 'user_id' links to staff

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
            'email' => [
                'sometimes', // Field is optional in the request
                'nullable',  // Value can be null
                'string',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customer->id), // Email must be unique, ignoring the current customer
            ],
            'phone' => 'sometimes|nullable|string|max:30',
            'address' => 'sometimes|nullable|string|max:1000',
        ]);

        try {
            $customer->update($validatedData);
            return new CustomerResource($customer);
        } catch (\Exception $e) {
            Log::error("Error updating customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update customer. Please try again.'], 500);
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