<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerProductType;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerProductTypeController extends Controller
{
    /**
     * Get all product types assigned to a customer
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function index(Customer $customer): JsonResponse
    {
        try {
            $customerProductTypes = CustomerProductType::with(['productType.category'])
                ->where('customer_id', $customer->id)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_type' => $customer->customerType,
                ],
                'product_types' => $customerProductTypes->map(function ($cpt) {
                    return [
                        'id' => $cpt->id,
                        'product_type' => [
                            'id' => $cpt->productType->id,
                            'name' => $cpt->productType->name,
                            'category' => $cpt->productType->category,
                            'is_dimension_based' => $cpt->productType->is_dimension_based,
                        ],
                        'is_active' => $cpt->is_active,
                        'created_at' => $cpt->created_at,
                    ];
                }),
                'total_count' => $customerProductTypes->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching customer product types: " . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch product types'], 500);
        }
    }

    /**
     * Import all available product types for a customer
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function importAll(Customer $customer): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get all product types
            $allProductTypes = ProductType::all();

            // Get existing customer product types
            $existingProductTypeIds = CustomerProductType::where('customer_id', $customer->id)
                ->pluck('product_type_id')
                ->toArray();

            // Filter out already assigned product types
            $newProductTypes = $allProductTypes->whereNotIn('id', $existingProductTypeIds);

            // Create new customer product type assignments
            $createdCount = 0;
            foreach ($newProductTypes as $productType) {
                CustomerProductType::create([
                    'customer_id' => $customer->id,
                    'product_type_id' => $productType->id,
                    'is_active' => true,
                ]);
                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully imported {$createdCount} product types",
                'imported_count' => $createdCount,
                'total_available' => $allProductTypes->count(),
                'already_assigned' => count($existingProductTypeIds),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error importing product types for customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to import product types'], 500);
        }
    }

    /**
     * Add a specific product type to a customer
     *
     * @param Customer $customer
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Customer $customer, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_type_id' => 'required|exists:product_types,id',
            ]);

            // Check if already assigned
            $existing = CustomerProductType::where('customer_id', $customer->id)
                ->where('product_type_id', $request->product_type_id)
                ->first();

            if ($existing) {
                if ($existing->is_active) {
                    return response()->json(['message' => 'Product type is already assigned to this customer'], 400);
                } else {
                    // Reactivate if it was deactivated
                    $existing->update(['is_active' => true]);
                    return response()->json(['message' => 'Product type reactivated for this customer']);
                }
            }

            // Create new assignment
            CustomerProductType::create([
                'customer_id' => $customer->id,
                'product_type_id' => $request->product_type_id,
                'is_active' => true,
            ]);

            return response()->json(['message' => 'Product type assigned successfully']);

        } catch (\Exception $e) {
            Log::error("Error assigning product type to customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to assign product type'], 500);
        }
    }

    /**
     * Remove a product type from a customer (soft delete by setting is_active to false)
     *
     * @param Customer $customer
     * @param CustomerProductType $customerProductType
     * @return JsonResponse
     */
    public function destroy(Customer $customer, CustomerProductType $customerProductType): JsonResponse
    {
        try {
            // Ensure the customer product type belongs to the customer
            if ($customerProductType->customer_id !== $customer->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $customerProductType->update(['is_active' => false]);

            return response()->json(['message' => 'Product type removed successfully']);

        } catch (\Exception $e) {
            Log::error("Error removing product type from customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to remove product type'], 500);
        }
    }

    /**
     * Get available product types that can be assigned to a customer
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function getAvailableProductTypes(Customer $customer): JsonResponse
    {
        try {
            // Get all product types
            $allProductTypes = ProductType::with('category')->get();

            // Get already assigned product type IDs
            $assignedProductTypeIds = CustomerProductType::where('customer_id', $customer->id)
                ->where('is_active', true)
                ->pluck('product_type_id')
                ->toArray();

            // Filter out already assigned product types
            $availableProductTypes = $allProductTypes->whereNotIn('id', $assignedProductTypeIds);

            return response()->json([
                'available_product_types' => $availableProductTypes->map(function ($pt) {
                    return [
                        'id' => $pt->id,
                        'name' => $pt->name,
                        'category' => $pt->category,
                        'is_dimension_based' => $pt->is_dimension_based,
                    ];
                }),
                'total_available' => $availableProductTypes->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching available product types for customer {$customer->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch available product types'], 500);
        }
    }
} 