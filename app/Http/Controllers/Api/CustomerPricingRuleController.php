<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PricingRule;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CustomerPricingRuleController extends Controller
{
    /**
     * Get all pricing rules for a customer with pagination, search, and sorting
     */
    public function index(Request $request, Customer $customer): JsonResponse
    {
        $query = $customer->pricingRules()
            ->with(['serviceOffering.productType.category', 'serviceOffering.serviceAction']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->whereHas('serviceOffering', function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhereHas('productType', function ($pq) use ($searchTerm) {
                      $pq->where('name', 'like', "%{$searchTerm}%")
                         ->orWhereHas('category', function ($cq) use ($searchTerm) {
                             $cq->where('name', 'like', "%{$searchTerm}%");
                         });
                  })
                  ->orWhereHas('serviceAction', function ($sq) use ($searchTerm) {
                      $sq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Category filter
        if ($request->has('category') && !empty($request->category)) {
            $category = $request->category;
            $query->whereHas('serviceOffering.productType.category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }

        // Product type filter
        if ($request->has('product_type_id') && !empty($request->product_type_id)) {
            $productTypeId = $request->product_type_id;
            $query->whereHas('serviceOffering.productType', function ($q) use ($productTypeId) {
                $q->where('id', $productTypeId);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['id', 'price', 'price_per_sq_meter', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }
        
        $query->orderBy($sortBy, $sortOrder);

        // Get total count before pagination
        $totalCount = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        
        // Validate per_page
        $allowedPerPage = [5, 10, 25, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $pricingRules = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'pricing_rules' => $pricingRules->items(),
            'total_count' => $totalCount,
            'current_page' => $pricingRules->currentPage(),
            'per_page' => $pricingRules->perPage(),
            'last_page' => $pricingRules->lastPage(),
            'from' => $pricingRules->firstItem(),
            'to' => $pricingRules->lastItem(),
        ]);
    }

    /**
     * Create a new pricing rule for a customer
     */
    public function store(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'service_offering_id' => 'required|exists:service_offerings,id',
            'price' => 'required|numeric|min:0',
            'price_per_sq_meter' => 'required|numeric|min:0',
        ]);

        // Check if pricing rule already exists for this customer and service offering
        $existingRule = PricingRule::where('customer_id', $customer->id)
            ->where('service_offering_id', $validated['service_offering_id'])
            ->first();

        if ($existingRule) {
            return response()->json([
                'message' => 'Pricing rule already exists for this service offering'
            ], 400);
        }

        $pricingRule = PricingRule::create([
            'customer_id' => $customer->id,
            'service_offering_id' => $validated['service_offering_id'],
            'price' => $validated['price'],
            'price_per_sq_meter' => $validated['price_per_sq_meter'],
        ]);

        $pricingRule->load(['serviceOffering.productType.category', 'serviceOffering.serviceAction']);

        return response()->json($pricingRule, 201);
    }

    /**
     * Update a pricing rule
     */
    public function update(Request $request, Customer $customer, PricingRule $pricingRule): JsonResponse
    {
        // Ensure the pricing rule belongs to the customer
        if ($pricingRule->customer_id !== $customer->id) {
            return response()->json(['message' => 'Pricing rule not found'], 404);
        }

        $validated = $request->validate([
            'price' => 'sometimes|numeric|min:0',
            'price_per_sq_meter' => 'sometimes|numeric|min:0',
        ]);

        $pricingRule->update($validated);
        $pricingRule->load(['serviceOffering.productType.category', 'serviceOffering.serviceAction']);

        return response()->json($pricingRule);
    }

    /**
     * Delete a pricing rule
     */
    public function destroy(Customer $customer, PricingRule $pricingRule): JsonResponse
    {
        // Ensure the pricing rule belongs to the customer
        if ($pricingRule->customer_id !== $customer->id) {
            return response()->json(['message' => 'Pricing rule not found'], 404);
        }

        $pricingRule->delete();

        return response()->json(['message' => 'Pricing rule deleted successfully']);
    }

    /**
     * Get available service offerings for a customer (those without pricing rules)
     */
    public function getAvailableServiceOfferings(Customer $customer): JsonResponse
    {
        // Get all service offerings
        $allServiceOfferings = ServiceOffering::with(['productType.category', 'serviceAction'])
            ->where('is_active', true)
            ->get();

        // Get service offering IDs that already have pricing rules for this customer
        $existingPricingRuleServiceOfferingIds = $customer->pricingRules()
            ->pluck('service_offering_id')
            ->toArray();

        // Filter out service offerings that already have pricing rules
        $availableServiceOfferings = $allServiceOfferings->filter(function ($serviceOffering) use ($existingPricingRuleServiceOfferingIds) {
            return !in_array($serviceOffering->id, $existingPricingRuleServiceOfferingIds);
        });

        return response()->json([
            'available_service_offerings' => $availableServiceOfferings->values()
        ]);
    }

    /**
     * Import all available service offerings as pricing rules for a customer
     */
    public function importAllServiceOfferings(Customer $customer): JsonResponse
    {
        // Get all active service offerings
        $allServiceOfferings = ServiceOffering::with(['productType.category', 'serviceAction'])
            ->where('is_active', true)
            ->get();

        // Get service offering IDs that already have pricing rules for this customer
        $existingPricingRuleServiceOfferingIds = $customer->pricingRules()
            ->pluck('service_offering_id')
            ->toArray();

        // Filter out service offerings that already have pricing rules
        $availableServiceOfferings = $allServiceOfferings->filter(function ($serviceOffering) use ($existingPricingRuleServiceOfferingIds) {
            return !in_array($serviceOffering->id, $existingPricingRuleServiceOfferingIds);
        });

        $createdCount = 0;
        $pricingRules = [];

        foreach ($availableServiceOfferings as $serviceOffering) {
            $pricingRule = PricingRule::create([
                'customer_id' => $customer->id,
                'service_offering_id' => $serviceOffering->id,
                'price' => $serviceOffering->default_price ?? 0,
                'price_per_sq_meter' => $serviceOffering->default_price_per_sq_meter ?? 0,
            ]);

            $pricingRule->load(['serviceOffering.productType.category', 'serviceOffering.serviceAction']);
            $pricingRules[] = $pricingRule;
            $createdCount++;
        }

        return response()->json([
            'message' => "Successfully imported {$createdCount} pricing rules",
            'created_count' => $createdCount,
            'pricing_rules' => $pricingRules
        ]);
    }

    /**
     * Get products that have pricing rules for a customer
     */
    public function getCustomerProductsWithPricingRules(Customer $customer): JsonResponse
    {
        // Get pricing rules for this customer with product type information
        $pricingRules = $customer->pricingRules()
            ->with(['serviceOffering.productType.category'])
            ->get();

        // Extract unique product types from pricing rules
        $productTypes = $pricingRules->map(function ($pricingRule) {
            return $pricingRule->serviceOffering->productType;
        })->unique('id')->values();

        return response()->json([
            'product_types' => $productTypes
        ]);
    }

    /**
     * Get all pricing rules for a customer (optimized for POS use)
     * Returns all pricing rules without pagination for quick access
     */
    public function getAllForCustomer(Customer $customer): JsonResponse
    {
        $pricingRules = $customer->pricingRules()
            ->with([
                'serviceOffering.productType.category', 
                'serviceOffering.serviceAction'
            ])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'pricing_rules' => $pricingRules,
            'total_count' => $pricingRules->count(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name
        ]);
    }
}
