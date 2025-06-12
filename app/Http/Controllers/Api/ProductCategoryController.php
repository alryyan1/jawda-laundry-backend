<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Resources\ProductCategoryResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * Typically not paginated for dropdowns, but can be if list is very long.
     */
    public function index(Request $request)
    {
        $query = ProductCategory::withCount('productTypes')->orderBy('name');

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }

        // Example: Paginate if requested, otherwise get all
        if ($request->has('paginate') && filter_var($request->paginate, FILTER_VALIDATE_BOOLEAN)) {
             $categories = $query->paginate($request->get('per_page', 15));
        } else {
             $categories = $query->get();
        }

        return ProductCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $category = ProductCategory::create($validatedData);
            return new ProductCategoryResource($category);
        } catch (\Exception $e) {
            Log::error("Error creating product category: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create product category.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory) // Route model binding
    {
        $productCategory->load('productTypes'); // Load related product types
        return new ProductCategoryResource($productCategory);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        $validatedData = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories')->ignore($productCategory->id),
            ],
            'description' => 'sometimes|nullable|string|max:1000',
        ]);

        try {
            $productCategory->update($validatedData);
            return new ProductCategoryResource($productCategory);
        } catch (\Exception $e) {
            Log::error("Error updating product category {$productCategory->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update product category.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        // Check if category is in use by any product types
        if ($productCategory->productTypes()->exists()) {
            return response()->json(['message' => 'Cannot delete category. It is currently assigned to product types. Please reassign or delete those product types first.'], 409); // Conflict
        }

        try {
            $productCategory->delete();
            return response()->json(['message' => 'Product category deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting product category {$productCategory->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete product category.'], 500);
        }
    }
}