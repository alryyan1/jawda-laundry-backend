<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\ProductCategory; // For validation if needed
use Illuminate\Http\Request;
use App\Http\Resources\ProductTypeResource; // Ensure you create this
use App\Http\Resources\ServiceActionResource;
use App\Models\ServiceAction;
use App\Models\ServiceOffering;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports pagination and filtering by product_category_id.
     */
    public function index(Request $request)
    {
        $query = ProductType::with('category')->orderBy('name'); // Eager load category

        if ($request->filled('product_category_id')) {
            $query->where('product_category_id', $request->product_category_id);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('category', function($cq) use ($searchTerm) {
                      $cq->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        $productTypes = $query->paginate($request->get('per_page', 15));
        return ProductTypeResource::collection($productTypes);
    }

    /**
     * Fetch all active product types, optionally filtered by category, for select dropdowns (non-paginated).
     */
    public function allForSelect(Request $request)
    {
        $query = ProductType::with('category:id,name') // Select only necessary columns for category
                              ->orderBy('name');
                            //   ->where('is_active', true); // Add if ProductType has an 'is_active' flag

        if ($request->filled('product_category_id')) {
            $query->where('product_category_id', $request->product_category_id);
        }

        $productTypes = $query->get();
        return ProductTypeResource::collection($productTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_types')->where(function ($query) use ($request) {
                    return $query->where('product_category_id', $request->product_category_id);
                }),
            ],
            'product_category_id' => 'required|integer|exists:product_categories,id',
            'description' => 'nullable|string|max:1000',
            'base_measurement_unit' => ['nullable', 'string', Rule::in(['item', 'kg', 'sq_meter', 'set', 'piece', 'other'])],
            // 'is_active' => 'sometimes|boolean', // If you add an is_active field
        ], [
            'name.unique' => 'This product type name already exists within the selected category.'
        ]);

        try {
            $productType = ProductType::create($validatedData);
            $productType->load('category'); // Eager load for response
            return new ProductTypeResource($productType);
        } catch (\Exception $e) {
            Log::error("Error creating product type: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create product type.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductType $productType) // Route model binding
    {
        $productType->load('category', 'serviceOfferings'); // Load category and its offerings
        return new ProductTypeResource($productType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductType $productType)
    {
        $validatedData = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_types')->where(function ($query) use ($request, $productType) {
                    // If product_category_id is not changing, use the existing one for uniqueness check
                    $categoryId = $request->input('product_category_id', $productType->product_category_id);
                    return $query->where('product_category_id', $categoryId);
                })->ignore($productType->id),
            ],
            'product_category_id' => 'sometimes|required|integer|exists:product_categories,id',
            'description' => 'sometimes|nullable|string|max:1000',
            'base_measurement_unit' => ['sometimes', 'nullable', 'string', Rule::in(['item', 'kg', 'sq_meter', 'set', 'piece', 'other'])],
            // 'is_active' => 'sometimes|boolean',
        ], [
            'name.unique' => 'This product type name already exists within the selected category.'
        ]);

        try {
            $productType->update($validatedData);
            $productType->load('category');
            return new ProductTypeResource($productType);
        } catch (\Exception $e) {
            Log::error("Error updating product type {$productType->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update product type.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductType $productType)
    {
        // Business logic: Check if this product type is used in any service offerings or order items
        if ($productType->serviceOfferings()->exists()) {
            return response()->json(['message' => 'Cannot delete product type. It is used in existing service offerings. Please remove or reassign those offerings first.'], 409); // Conflict
        }
        // You might also check order_items if they directly reference product_type_id (though our model uses service_offering_id)

        try {
            $productType->delete();
            return response()->json(['message' => 'Product type deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting product type {$productType->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete product type.'], 500);
        }
    }
    public function availableServiceActions(ProductType $productType)
    {
        // Find ServiceAction IDs that have a ServiceOffering with the given ProductType
        $serviceActionIds = ServiceOffering::where('product_type_id', $productType->id)
                                           ->where('is_active', true) // Only active offerings
                                           ->pluck('service_action_id')
                                           ->unique();

        $serviceActions = ServiceAction::whereIn('id', $serviceActionIds)
                                      // ->where('is_active', true) // If ServiceAction itself has an active flag
                                      ->orderBy('name')
                                      ->get();

        return ServiceActionResource::collection($serviceActions);
        // Alternatively, you could return partial ServiceOffering data here if that's more useful
        // e.g., return ServiceOffering::where('product_type_id', $productType->id)->with('serviceAction')->get();
        // then the frontend would get ServiceOffering ID directly. For now, returning ServiceActions.
    }
}