<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerProductServiceOffering;
use App\Models\ServiceOffering;
use App\Models\ServiceAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerServiceOfferingController extends Controller
{
    /**
     * Get service offerings for a customer's product type
     */
    public function index(Customer $customer, int $productTypeId): JsonResponse
    {
        try {
            // Get customer-specific service offerings for this product type only
            $customerServiceOfferings = CustomerProductServiceOffering::with(['serviceAction', 'productType'])
                ->where('customer_id', $customer->id)
                ->where('product_type_id', $productTypeId)
                ->get();

            // Transform the data to match the expected format
            $result = $customerServiceOfferings->map(function ($customerServiceOffering) {
                return [
                    'id' => $customerServiceOffering->id,
                    'service_action' => $customerServiceOffering->serviceAction,
                    'product_type' => $customerServiceOffering->productType,
                    'name_override' => $customerServiceOffering->name_override,
                    'description_override' => $customerServiceOffering->description_override,
                    'default_price' => $customerServiceOffering->default_price,
                    'default_price_per_sq_meter' => $customerServiceOffering->default_price_per_sq_meter,
                    'applicable_unit' => $customerServiceOffering->applicable_unit,
                    'custom_price' => $customerServiceOffering->custom_price,
                    'custom_price_per_sq_meter' => $customerServiceOffering->custom_price_per_sq_meter,
                    'is_active' => $customerServiceOffering->is_active,
                    'valid_from' => $customerServiceOffering->valid_from,
                    'valid_to' => $customerServiceOffering->valid_to,
                    'min_quantity' => $customerServiceOffering->min_quantity,
                    'min_area_sq_meter' => $customerServiceOffering->min_area_sq_meter,
                    'has_custom_pricing' => true, // All returned items have custom pricing
                ];
            });

            return response()->json([
                'service_offerings' => $result,
                'total' => $result->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching service offerings for customer {$customer->id}, product type {$productTypeId}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch service offerings'], 500);
        }
    }

    /**
     * Create customer-specific pricing for a service action
     */
    public function store(Request $request, Customer $customer, int $productTypeId): JsonResponse
    {
        try {
            $request->validate([
                'service_action_id' => 'required|integer|exists:service_actions,id',
                'name_override' => 'nullable|string|max:255',
                'description_override' => 'nullable|string',
                'default_price' => 'nullable|numeric|min:0',
                'default_price_per_sq_meter' => 'nullable|numeric|min:0',
                'applicable_unit' => 'nullable|string|max:50',
                'custom_price' => 'nullable|numeric|min:0',
                'custom_price_per_sq_meter' => 'nullable|numeric|min:0',
                'is_active' => 'nullable|boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
                'min_quantity' => 'nullable|integer|min:1',
                'min_area_sq_meter' => 'nullable|numeric|min:0',
            ]);

            // Check if customer service offering already exists
            $existingServiceOffering = CustomerProductServiceOffering::where('customer_id', $customer->id)
                ->where('product_type_id', $productTypeId)
                ->where('service_action_id', $request->service_action_id)
                ->first();

            if ($existingServiceOffering) {
                return response()->json(['message' => 'Customer service offering already exists for this service action'], 409);
            }

            // Create customer-specific service offering
            $customerServiceOffering = CustomerProductServiceOffering::create([
                'customer_id' => $customer->id,
                'product_type_id' => $productTypeId,
                'service_action_id' => $request->service_action_id,
                'name_override' => $request->name_override,
                'description_override' => $request->description_override,
                'default_price' => $request->default_price,
                'default_price_per_sq_meter' => $request->default_price_per_sq_meter,
                'applicable_unit' => $request->applicable_unit,
                'custom_price' => $request->custom_price,
                'custom_price_per_sq_meter' => $request->custom_price_per_sq_meter,
                'is_active' => $request->is_active ?? true,
                'valid_from' => $request->valid_from,
                'valid_to' => $request->valid_to,
                'min_quantity' => $request->min_quantity,
                'min_area_sq_meter' => $request->min_area_sq_meter,
            ]);

            return response()->json([
                'message' => 'Customer service offering created successfully',
                'customer_service_offering' => $customerServiceOffering->load(['serviceAction', 'productType']),
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error creating customer service offering for customer {$customer->id}, product type {$productTypeId}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create customer service offering'], 500);
        }
    }

    /**
     * Update customer-specific pricing for a service action
     */
    public function update(Request $request, Customer $customer, int $productTypeId, int $serviceActionId): JsonResponse
    {
        try {
            $request->validate([
                'name_override' => 'nullable|string|max:255',
                'description_override' => 'nullable|string',
                'default_price' => 'nullable|numeric|min:0',
                'default_price_per_sq_meter' => 'nullable|numeric|min:0',
                'applicable_unit' => 'nullable|string|max:50',
                'custom_price' => 'nullable|numeric|min:0',
                'custom_price_per_sq_meter' => 'nullable|numeric|min:0',
                'is_active' => 'nullable|boolean',
                'valid_from' => 'nullable|date',
                'valid_to' => 'nullable|date|after_or_equal:valid_from',
                'min_quantity' => 'nullable|integer|min:1',
                'min_area_sq_meter' => 'nullable|numeric|min:0',
            ]);

            // Find the customer service offering record
            $customerServiceOffering = CustomerProductServiceOffering::where('customer_id', $customer->id)
                ->where('product_type_id', $productTypeId)
                ->where('service_action_id', $serviceActionId)
                ->first();

            if (!$customerServiceOffering) {
                return response()->json(['message' => 'Customer service offering not found'], 404);
            }

            // Update the service offering
            $updateData = $request->only([
                'name_override',
                'description_override',
                'default_price',
                'default_price_per_sq_meter',
                'applicable_unit',
                'custom_price',
                'custom_price_per_sq_meter',
                'is_active',
                'valid_from',
                'valid_to',
                'min_quantity',
                'min_area_sq_meter',
            ]);

            $customerServiceOffering->update($updateData);

            return response()->json([
                'message' => 'Customer service offering updated successfully',
                'customer_service_offering' => $customerServiceOffering->fresh()->load(['serviceAction', 'productType']),
            ]);

        } catch (\Exception $e) {
            Log::error("Error updating customer service offering for customer {$customer->id}, product type {$productTypeId}, service action {$serviceActionId}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update customer service offering'], 500);
        }
    }

    /**
     * Delete customer-specific pricing for a service action
     */
    public function destroy(Customer $customer, int $productTypeId, int $serviceActionId): JsonResponse
    {
        try {
            // Find and delete the customer service offering record
            $customerServiceOffering = CustomerProductServiceOffering::where('customer_id', $customer->id)
                ->where('product_type_id', $productTypeId)
                ->where('service_action_id', $serviceActionId)
                ->first();

            if (!$customerServiceOffering) {
                return response()->json(['message' => 'Customer service offering not found'], 404);
            }

            $customerServiceOffering->delete();

            return response()->json([
                'message' => 'Customer service offering deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting customer service offering for customer {$customer->id}, product type {$productTypeId}, service action {$serviceActionId}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete customer service offering'], 500);
        }
    }
} 