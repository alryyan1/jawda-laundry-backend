<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceOfferingResource; // Ensure you have this resource
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\ProductType; // For validation
use App\Models\ServiceAction; // For validation

class ServiceOfferingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceOffering::with([
            'productType.category', // Eager load nested relationships
            'serviceAction'
        ]);

        // Example Filters:
        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->product_type_id);
        }
        if ($request->filled('service_action_id')) {
            $query->where('service_action_id', $request->service_action_id);
        }
        if ($request->has('is_active')) { // Allows filtering by true or false
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name_override', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('productType', function ($ptQuery) use ($searchTerm) {
                      $ptQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  })
                  ->orWhereHas('serviceAction', function ($saQuery) use ($searchTerm) {
                      $saQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Default sort or allow sorting from request
        $sortBy = $request->get('sort_by', 'created_at'); // Default sort column
        $sortDirection = $request->get('sort_direction', 'desc'); // Default sort direction
        // Validate sort_by to prevent SQL injection if directly using user input
        $allowedSortColumns = ['id', 'name_override', 'default_price', 'created_at', /* add productType.name, serviceAction.name if you implement complex sort */];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc'); // Fallback sort
        }


        $offerings = $query->paginate($request->get('per_page', 15));
        return ServiceOfferingResource::collection($offerings);
    }

    /**
     * Fetch all active service offerings for select dropdowns (non-paginated).
     */
    public function allForSelect(Request $request)
    {
        $query = ServiceOffering::with(['productType:id,name,product_category_id', 'productType.category:id,name', 'serviceAction:id,name'])
                                 ->where('is_active', true)
                                 ->orderBy('name_override'); // Or by productType.name then serviceAction.name

        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->product_type_id);
        }

        // You might want to order these in a way that makes sense for dropdowns
        // e.g., by productType.name then by serviceAction.name
        // This requires joining or more complex ordering if 'name_override' is not consistently used.
        // For now, a simple order by name_override or product type name.
        $offerings = $query->get()->sortBy(function($offering) { // Client-side sort for display_name after get()
            return $offering->display_name;
        });


        return ServiceOfferingResource::collection($offerings);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_type_id' => 'required|integer|exists:product_types,id',
            'service_action_id' => 'required|integer|exists:service_actions,id',
            'name_override' => 'nullable|string|max:255',
            'description_override' => 'nullable|string|max:1000',
            'default_price' => 'nullable|numeric|min:0',
            'pricing_strategy' => ['required', Rule::in(['fixed', 'per_unit_product', 'dimension_based', 'customer_specific'])],
            'default_price_per_sq_meter' => 'nullable|numeric|min:0|required_if:pricing_strategy,dimension_based',
            'applicable_unit' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ], [
            'default_price_per_sq_meter.required_if' => 'The default price per square meter is required when pricing strategy is dimension based.'
        ]);

        // Ensure uniqueness for the combination of product_type_id and service_action_id
        $exists = ServiceOffering::where('product_type_id', $validatedData['product_type_id'])
                                 ->where('service_action_id', $validatedData['service_action_id'])
                                 ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'The combination of this Product Type and Service Action already exists as a Service Offering.'
            ], 422);
        }

        try {
            $offering = ServiceOffering::create($validatedData);
            $offering->load(['productType.category', 'serviceAction']); // Eager load for response
            return new ServiceOfferingResource($offering);
        } catch (\Exception $e) {
            Log::error("Error creating service offering: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create service offering.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOffering $serviceOffering) // Route model binding
    {
        $serviceOffering->load(['productType.category', 'serviceAction', 'pricingRules.customer', 'pricingRules.customerType']); // Load related for details
        return new ServiceOfferingResource($serviceOffering);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceOffering $serviceOffering)
    {
         $validatedData = $request->validate([
            'product_type_id' => 'sometimes|required|integer|exists:product_types,id',
            'service_action_id' => 'sometimes|required|integer|exists:service_actions,id',
            'name_override' => 'nullable|string|max:255',
            'description_override' => 'nullable|string|max:1000',
            'default_price' => 'nullable|numeric|min:0',
            'pricing_strategy' => ['sometimes', 'required', Rule::in(['fixed', 'per_unit_product', 'dimension_based', 'customer_specific'])],
            'default_price_per_sq_meter' => 'nullable|numeric|min:0|required_if:pricing_strategy,dimension_based',
            'applicable_unit' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check uniqueness if product_type_id or service_action_id is being changed
        if (($request->has('product_type_id') && $request->product_type_id != $serviceOffering->product_type_id) ||
            ($request->has('service_action_id') && $request->service_action_id != $serviceOffering->service_action_id))
        {
            $ptId = $request->product_type_id ?? $serviceOffering->product_type_id;
            $saId = $request->service_action_id ?? $serviceOffering->service_action_id;

            $exists = ServiceOffering::where('product_type_id', $ptId)
                                     ->where('service_action_id', $saId)
                                     ->where('id', '!=', $serviceOffering->id) // Exclude current offering
                                     ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'The combination of this Product Type and Service Action already exists for another Service Offering.'
                ], 422);
            }
        }


        try {
            $serviceOffering->update($validatedData);
            $serviceOffering->load(['productType.category', 'serviceAction']);
            return new ServiceOfferingResource($serviceOffering);
        } catch (\Exception $e) {
            Log::error("Error updating service offering {$serviceOffering->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update service offering.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceOffering $serviceOffering)
    {
        // Consider business logic: e.g., can't delete if part of non-completed orders
        // if ($serviceOffering->orderItems()->whereHas('order', fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))->exists()) {
        //     return response()->json(['message' => 'Cannot delete service offering. It is part of active orders.'], 409);
        // }

        try {
            // If it has pricing rules, you might want to delete them or handle them (cascade on delete in DB?)
            // $serviceOffering->pricingRules()->delete();
            $serviceOffering->delete(); // Assuming no soft deletes on ServiceOffering, or handle accordingly
            return response()->json(['message' => 'Service offering deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting service offering {$serviceOffering->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete service offering.'], 500);
        }
    }
}