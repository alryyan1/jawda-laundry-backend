<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceOffering;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
            // Get all active service offerings
            $serviceOfferings = ServiceOffering::with([
                'productType',
                'serviceAction'
            ])
                ->where('is_active', true)
                ->get()
                ->map(function ($offering) {
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
                        'effective_price' => $offering->default_price ?? $offering->default_price_per_sq_meter,
                    ];
                });

            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
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
            $csvData[] = ['Product', 'Service', 'Price'];

            foreach ($priceList->price_list as $item) {
                $csvData[] = [
                    $item->product_type->name,
                    $item->service_action->name,
                    $item->effective_price ?? 'N/A',
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
