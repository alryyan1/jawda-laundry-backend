<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\PredefinedSize;
use App\Models\ProductType;
use Illuminate\Http\Request;
use App\Http\Resources\PredefinedSizeResource;

class PredefinedSizeController extends Controller {
    public function __construct() {
        // Authorization middleware removed
    }

    // List all predefined sizes for a specific product type
    public function index(ProductType $productType) {
        return PredefinedSizeResource::collection($productType->predefinedSizes()->orderBy('name')->get());
    }

    // Store a new predefined size for a specific product type
    public function store(Request $request, ProductType $productType) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'length_meters' => 'required|numeric|min:0.01',
            'width_meters' => 'required|numeric|min:0.01',
        ]);
        $size = $productType->predefinedSizes()->create($validated);
        return new PredefinedSizeResource($size);
    }

    public function destroy(ProductType $productType, PredefinedSize $predefinedSize) {
        // Ensure the size belongs to the product type for security
        if ($predefinedSize->product_type_id !== $productType->id) {
            abort(404);
        }
        $predefinedSize->delete();
        return response()->noContent();
    }
}