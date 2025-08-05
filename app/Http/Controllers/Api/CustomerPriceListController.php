<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PricingRule;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CustomerPriceListController extends Controller
{
    /**
     * Get the price list for a specific customer
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function show(Customer $customer): JsonResponse
    {
        try {
            // Get all service offerings with their pricing rules for this customer
            $serviceOfferings = ServiceOffering::with([
                'productType',
                'serviceAction',
                'pricingRules' => function ($query) use ($customer) {
                    $query->where('customer_id', $customer->id)
                          ->where(function ($q) {
                              $q->whereNull('valid_from')
                                ->orWhere('valid_from', '<=', now());
                          })
                          ->where(function ($q) {
                              $q->whereNull('valid_to')
                                ->orWhere('valid_to', '>=', now());
                          });
                }
            ])
            ->where('is_active', true)
            ->get()
            ->map(function ($offering) use ($customer) {
                // Get the most specific pricing rule for this customer
                $customerRule = $offering->pricingRules->first();
                
                // Get customer type rule if no specific customer rule
                $typeRule = null;
                if (!$customerRule && $customer->customer_type_id) {
                    $typeRule = PricingRule::where('service_offering_id', $offering->id)
                        ->where('customer_type_id', $customer->customer_type_id)
                        ->where(function ($q) {
                            $q->whereNull('valid_from')
                              ->orWhere('valid_from', '<=', now());
                        })
                        ->where(function ($q) {
                            $q->whereNull('valid_to')
                              ->orWhere('valid_to', '>=', now());
                        })
                        ->first();
                }

                return [
                    'id' => $offering->id,
                    'product_type' => [
                        'id' => $offering->productType->id,
                        'name' => $offering->productType->name,
                        'is_dimension_based' => $offering->productType->is_dimension_based,
                    ],
                    'service_action' => [
                        'id' => $offering->serviceAction->id,
                        'name' => $offering->serviceAction->name,
                    ],
                    'display_name' => $offering->name_override ?: $offering->productType->name . ' - ' . $offering->serviceAction->name,
                    'default_price' => $offering->default_price,
                    'default_price_per_sq_meter' => $offering->default_price_per_sq_meter,
                    'pricing_strategy' => $offering->pricing_strategy,
                    'applicable_unit' => $offering->applicable_unit,
                    'customer_specific_price' => $customerRule ? [
                        'price' => $customerRule->price,
                        'price_per_sq_meter' => $customerRule->price_per_sq_meter,
                        'valid_from' => $customerRule->valid_from,
                        'valid_to' => $customerRule->valid_to,
                        'min_quantity' => $customerRule->min_quantity,
                        'min_area_sq_meter' => $customerRule->min_area_sq_meter,
                    ] : null,
                    'customer_type_price' => $typeRule ? [
                        'price' => $typeRule->price,
                        'price_per_sq_meter' => $typeRule->price_per_sq_meter,
                        'valid_from' => $typeRule->valid_from,
                        'valid_to' => $typeRule->valid_to,
                        'min_quantity' => $typeRule->min_quantity,
                        'min_area_sq_meter' => $typeRule->min_area_sq_meter,
                    ] : null,
                    'effective_price' => $customerRule ? ($customerRule->price ?? $customerRule->price_per_sq_meter) : 
                                        ($typeRule ? ($typeRule->price ?? $typeRule->price_per_sq_meter) : 
                                        ($offering->default_price ?? $offering->default_price_per_sq_meter)),
                ];
            });

            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_type' => $customer->customerType,
                ],
                'price_list' => $serviceOfferings,
                'total_items' => $serviceOfferings->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching customer price list: " . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch price list'], 500);
        }
    }

    /**
     * Update or create pricing rules for a customer
     *
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validatedData = $request->validate([
            'pricing_rules' => 'required|array',
            'pricing_rules.*.service_offering_id' => 'required|exists:service_offerings,id',
            'pricing_rules.*.price' => 'nullable|numeric|min:0',
            'pricing_rules.*.price_per_sq_meter' => 'nullable|numeric|min:0',
            'pricing_rules.*.valid_from' => 'nullable|date',
            'pricing_rules.*.valid_to' => 'nullable|date|after_or_equal:pricing_rules.*.valid_from',
            'pricing_rules.*.min_quantity' => 'nullable|integer|min:1',
            'pricing_rules.*.min_area_sq_meter' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validatedData['pricing_rules'] as $ruleData) {
                $serviceOfferingId = $ruleData['service_offering_id'];
                
                // Remove null values
                $ruleData = array_filter($ruleData, function ($value) {
                    return $value !== null;
                });

                // Remove service_offering_id from the data to be saved
                unset($ruleData['service_offering_id']);

                // If no pricing data provided, delete existing rule
                if (empty(array_intersect_key($ruleData, array_flip(['price', 'price_per_sq_meter'])))) {
                    PricingRule::where('service_offering_id', $serviceOfferingId)
                        ->where('customer_id', $customer->id)
                        ->delete();
                    continue;
                }

                // Update or create the pricing rule
                PricingRule::updateOrCreate(
                    [
                        'service_offering_id' => $serviceOfferingId,
                        'customer_id' => $customer->id,
                    ],
                    $ruleData
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Price list updated successfully',
                'customer_id' => $customer->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating customer price list: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update price list'], 500);
        }
    }

    /**
     * Delete all pricing rules for a customer
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $deletedCount = PricingRule::where('customer_id', $customer->id)->delete();

            return response()->json([
                'message' => 'Price list deleted successfully',
                'deleted_rules' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting customer price list: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete price list'], 500);
        }
    }

    /**
     * Get a summary of customer pricing rules
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function summary(Customer $customer): JsonResponse
    {
        try {
            $pricingRules = PricingRule::where('customer_id', $customer->id)
                ->with(['serviceOffering.productType', 'serviceOffering.serviceAction'])
                ->get();

            $summary = [
                'total_rules' => $pricingRules->count(),
                'active_rules' => $pricingRules->filter(function ($rule) {
                    return (!$rule->valid_from || $rule->valid_from <= now()) &&
                           (!$rule->valid_to || $rule->valid_to >= now());
                })->count(),
                'expired_rules' => $pricingRules->filter(function ($rule) {
                    return $rule->valid_to && $rule->valid_to < now();
                })->count(),
                'future_rules' => $pricingRules->filter(function ($rule) {
                    return $rule->valid_from && $rule->valid_from > now();
                })->count(),
                'categories_covered' => $pricingRules->pluck('serviceOffering.productType.product_category_id')->unique()->count(),
            ];

            return response()->json($summary);

        } catch (\Exception $e) {
            Log::error("Error fetching customer price list summary: " . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch summary'], 500);
        }
    }

    /**
     * Export customer price list as CSV
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function export(Customer $customer): JsonResponse
    {
        try {
            $priceList = $this->show($customer)->getData();
            
            // Generate CSV content
            $csvData = [];
            $csvData[] = ['Product', 'Service', 'Default Price', 'Customer Price', 'Valid From', 'Valid To'];
            
            foreach ($priceList->price_list as $item) {
                $csvData[] = [
                    $item->product_type->name,
                    $item->service_action->name,
                    $item->default_price ?? $item->default_price_per_sq_meter ?? 'N/A',
                    $item->customer_specific_price ? 
                        ($item->customer_specific_price->price ?? $item->customer_specific_price->price_per_sq_meter ?? 'N/A') : 
                        'N/A',
                    $item->customer_specific_price ? 
                        ($item->customer_specific_price->valid_from ?? 'Always') : 
                        'N/A',
                    $item->customer_specific_price ? 
                        ($item->customer_specific_price->valid_to ?? 'Always') : 
                        'N/A',
                ];
            }

            return response()->json([
                'csv_data' => $csvData,
                'filename' => "price_list_{$customer->name}_" . date('Y-m-d') . ".csv",
            ]);

        } catch (\Exception $e) {
            Log::error("Error exporting customer price list: " . $e->getMessage());
            return response()->json(['message' => 'Failed to export price list'], 500);
        }
    }
} 