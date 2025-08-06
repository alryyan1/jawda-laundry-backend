<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;
use App\Http\Resources\CustomerTypeResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CustomerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Typically not paginated for dropdowns.
     */
    public function index(Request $request)
    {
        // Add authorization if needed: e.g., $this->authorize('viewAny', CustomerType::class);

        $query = CustomerType::withCount(['customers'])->orderBy('name');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Usually, for dropdowns, you want all types. Paginate for an admin list view if needed.
        if ($request->has('paginate') && filter_var($request->paginate, FILTER_VALIDATE_BOOLEAN)) {
             $customerTypes = $query->paginate($request->get('per_page', 15));
        } else {
             $customerTypes = $query->get();
        }

        return CustomerTypeResource::collection($customerTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Add authorization: e.g., $this->authorize('create', CustomerType::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:customer_types,name',
            'description' => 'nullable|string|max:1000',
            // Add validation for other fields like 'discount_percentage' if you have them
        ]);

        try {
            $customerType = CustomerType::create($validatedData);
            return new CustomerTypeResource($customerType);
        } catch (\Exception $e) {
            Log::error("Error creating customer type: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create customer type.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerType $customerType) // Route model binding
    {
        // Add authorization: e.g., $this->authorize('view', $customerType);
        $customerType->loadCount(['customers']);
        return new CustomerTypeResource($customerType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerType $customerType)
    {
        // Add authorization: e.g., $this->authorize('update', $customerType);

        $validatedData = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('customer_types')->ignore($customerType->id),
            ],
            'description' => 'sometimes|nullable|string|max:1000',
            // Add validation for other updatable fields
        ]);

        try {
            $customerType->update($validatedData);
            return new CustomerTypeResource($customerType);
        } catch (\Exception $e) {
            Log::error("Error updating customer type {$customerType->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update customer type.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerType $customerType)
    {
        // Add authorization: e.g., $this->authorize('delete', $customerType);

        // Check if this customer type is in use by any customers or pricing rules
        if ($customerType->customers()->exists()) {
            return response()->json(['message' => 'Cannot delete customer type. It is currently assigned to customers. Please reassign those customers first.'], 409); // Conflict
        }


        try {
            $customerType->delete(); // Will soft delete if SoftDeletes trait is used
            return response()->json(['message' => 'Customer type deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting customer type {$customerType->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete customer type.'], 500);
        }
    }
}