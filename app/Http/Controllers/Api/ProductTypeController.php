<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\ProductCategory; // For validation if needed
use Illuminate\Http\Request;
use App\Http\Resources\ProductTypeResource; // Ensure you create this
use App\Http\Resources\ServiceActionResource;
use App\Http\Resources\ServiceOfferingResource;
use App\Models\ServiceAction;
use App\Models\ServiceOffering;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports pagination and filtering by product_category_id.
     */
    public function index(Request $request)
    {
        $query = ProductType::with('category'); // Eager load category

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

        // Handle sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort_by to prevent SQL injection
        $allowedSortFields = ['id', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        
        // Validate sort_order
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $productTypes = $query->orderBy($sortBy, $sortOrder)->paginate($request->get('per_page', 15));
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
            'is_dimension_based' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'is_active' => 'sometimes|boolean', // If you add an is_active field
        ], [
            'name.unique' => 'This product type name already exists within the selected category.'
        ]);
        $imageUrl = null;
        if ($request->hasFile('image')) {
            // Store the file in 'storage/app/public/product_types' and get its path
            $path = $request->file('image')->store('product_types', 'public');
            // Get the public URL for the stored file
            $imageUrl = asset('storage/' . $path);
        }
        // Add the image URL to the data to be created
        $validatedData['image_url'] = $imageUrl;


        try {
            $productType = ProductType::create($validatedData);
            $productType->load('category');
            return new ProductTypeResource($productType);
        } catch (\Exception $e) {
            // If creation fails, delete the uploaded image if it exists
            if ($imageUrl) {
                Storage::disk('public')->delete($path);
            }
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
            'is_dimension_based' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'is_active' => 'sometimes|boolean',
        ], [
            'name.unique' => 'This product type name already exists within the selected category.'
        ]);
        $imageUrl = $productType->image_url; // Keep old image by default
        $newPath = null;

        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($productType->image_url) {
                // Extract path from URL to delete from storage
                $oldPath = str_replace(asset('storage/'), '', $productType->image_url);
                Storage::disk('public')->delete($oldPath);
            }

            // Store the new file
            $newPath = $request->file('image')->store('product_types', 'public');
            $imageUrl = asset('storage/' . $newPath);
        }
        // Update the validatedData with the new image URL if an image was uploaded
        if ($newPath) {
            $validatedData['image_url'] = $imageUrl;
        }

        try {
            $productType->update($validatedData);
            $productType->load('category');
            return new ProductTypeResource($productType);
        } catch (\Exception $e) {
            // If update fails and we uploaded a new image, delete it
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            Log::error("Error updating product type {$productType->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update product type.', 'error' => $e->getMessage()], 500);
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
 
    public function createAllOfferings(ProductType $productType)
    {
        $this->authorize('update', $productType); // Or a more specific permission like 'service_offering_manage'

        // Find which Service Actions already have an offering for this Product Type
        $existingActionIds = ServiceOffering::where('product_type_id', $productType->id)
                                            ->pluck('service_action_id');

        // Find all Service Actions that DON'T have an offering yet
        $missingActions = ServiceAction::whereNotIn('id', $existingActionIds)->get();

        if ($missingActions->isEmpty()) {
            return response()->json(['message' => 'All available service actions already have offerings for this product type.'], 200);
        }

        $newOfferingsData = [];
        foreach ($missingActions as $action) {
            $newOfferingsData[] = [
                'product_type_id' => $productType->id,
                'service_action_id' => $action->id,
                // Set defaults. Price is 0 so admin is forced to set it.
                'default_price' => 0.00,
                'default_price_per_sq_meter' => 0.00,
                'is_active' => true, // Default to active, admin can deactivate
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ServiceOffering::insert($newOfferingsData); // Bulk insert for efficiency

        // Fetch all offerings for this product type to return the complete, updated list
        $allOfferings = ServiceOffering::where('product_type_id', $productType->id)
                                       ->with(['serviceAction', 'productType'])
                                       ->get();

        return ServiceOfferingResource::collection($allOfferings);
    }

    public function availableServiceActions(ProductType $productType)
    {
        // Find ServiceAction IDs that have an active ServiceOffering for the given ProductType
        $serviceActionIds = ServiceOffering::where('product_type_id', $productType->id)
                                           ->where('is_active', true)
                                           ->pluck('service_action_id')
                                           ->unique();

        $serviceActions = ServiceAction::whereIn('id', $serviceActionIds)
                                      ->orderBy('name')
                                      ->get();

        return ServiceActionResource::collection($serviceActions);
    }
}