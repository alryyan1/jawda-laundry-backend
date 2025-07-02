<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use App\Http\Resources\ServiceOfferingResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ServiceOfferingController extends Controller
{
    /**
     * Apply authorization middleware.
     */
    public function __construct()
    {
        // A single permission to manage all service-related components is simple and effective
        // $this->middleware('can:service-admin:manage');
    }

    /**
     * Display a paginated listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceOffering::with([
            'productType.category', // Eager load nested relationships for display
            'serviceAction'
        ]);

        if ($request->filled('product_type_id')) {
            $query->where('product_type_id', $request->product_type_id);
        }

        // Filtering
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name_override', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('productType', fn($ptQuery) => $ptQuery->where('name', 'LIKE', "%{$searchTerm}%"))
                    ->orWhereHas('serviceAction', fn($saQuery) => $saQuery->where('name', 'LIKE', "%{$searchTerm}%"));
            });
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $allowedSorts = ['id', 'name_override', 'default_price', 'created_at'];
        if (in_array($sortBy, $allowedSorts) && in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $offerings = $query->paginate($request->get('per_page', 15));
        return ServiceOfferingResource::collection($offerings);
    }

    /**
     * Fetch all active service offerings, typically for dropdowns or selection panels.
     */
    public function allForSelect(Request $request)
    {
        $query = ServiceOffering::with([
            'productType:id,name,is_dimension_based,product_category_id', // Only what's needed
            'serviceAction:id,name'
        ])
            ->where('is_active', true)
            ->join('product_types', 'service_offerings.product_type_id', '=', 'product_types.id')
            ->orderBy('product_types.name') // Order by product type name first
            ->select('service_offerings.*'); // Select all from service_offerings to avoid conflicts

        if ($request->filled('product_type_id')) {
            $query->where('service_offerings.product_type_id', $request->product_type_id);
        }

        $offerings = $query->get();

        return ServiceOfferingResource::collection($offerings);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_type_id' => [
                'required',
                'integer',
                'exists:product_types,id',
                // Ensure this combination is unique
                Rule::unique('service_offerings')->where(function ($query) use ($request) {
                    return $query->where('service_action_id', $request->service_action_id);
                }),
            ],
            'service_action_id' => 'required|integer|exists:service_actions,id',
            'name_override' => 'nullable|string|max:255',
            'description_override' => 'nullable|string|max:1000',
            'default_price' => 'nullable|numeric|min:0',
            'default_price_per_sq_meter' => 'nullable|numeric|min:0',
            'applicable_unit' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ], [
            'product_type_id.unique' => 'This service action is already offered for this product type.'
        ]);

        try {
            $offering = ServiceOffering::create($validatedData);
            $offering->load(['productType.category', 'serviceAction']);
            return new ServiceOfferingResource($offering);
        } catch (\Exception $e) {
            Log::error("Error creating service offering: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create service offering.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceOffering $serviceOffering)
    {
        $serviceOffering->load(['productType.category', 'serviceAction']);
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
            'name_override' => 'sometimes|nullable|string|max:255',
            'description_override' => 'sometimes|nullable|string|max:1000',
            'default_price' => 'sometimes|nullable|numeric|min:0',
            'default_price_per_sq_meter' => 'sometimes|nullable|numeric|min:0',
            'applicable_unit' => 'sometimes|nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check for uniqueness if the combination is being changed
        $ptId = $request->input('product_type_id', $serviceOffering->product_type_id);
        $saId = $request->input('service_action_id', $serviceOffering->service_action_id);
        if ($ptId != $serviceOffering->product_type_id || $saId != $serviceOffering->service_action_id) {
            $exists = ServiceOffering::where('product_type_id', $ptId)
                ->where('service_action_id', $saId)
                ->where('id', '!=', $serviceOffering->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'This service action is already offered for this product type.'], 422);
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
        // Business logic: check if the offering is part of any non-completed/non-cancelled orders.
        if ($serviceOffering->orderItems()->whereHas('order', fn($q) => $q->whereNotIn('status', ['completed', 'cancelled']))->exists()) {
            return response()->json(['message' => 'Cannot delete service offering. It is part of active orders.'], 409); // 409 Conflict
        }

        try {
            $serviceOffering->delete();
            return response()->json(['message' => 'Service offering deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Error deleting service offering {$serviceOffering->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete service offering.'], 500);
        }
    }
}