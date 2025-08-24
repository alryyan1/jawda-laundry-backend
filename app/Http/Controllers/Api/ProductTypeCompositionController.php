<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductType;
use App\Models\ProductTypeComposition;
use App\Models\ProductComposition;
use App\Http\Resources\ProductTypeCompositionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductTypeCompositionController extends Controller
{
    /**
     * عرض جميع مكونات نوع منتج معين
     */
    public function index($productTypeId): JsonResponse
    {
        try {
            $compositions = ProductTypeComposition::with('productComposition')
                ->where('product_type_id', $productTypeId)
                ->get();
            

            
            return response()->json([
                'success' => true,
                'data' => ProductTypeCompositionResource::collection($compositions)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المكونات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء مكون جديد
     */
    public function store(Request $request, $productTypeId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_composition_id' => 'required|exists:product_compositions,id',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productType = ProductType::findOrFail($productTypeId);

            // Check if this composition is already assigned to this product type
            $existingComposition = ProductTypeComposition::where('product_type_id', $productTypeId)
                ->where('product_composition_id', $request->product_composition_id)
                ->first();

            if ($existingComposition) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا المكون موجود بالفعل لهذا النوع من المنتجات'
                ], 422);
            }

            $composition = ProductTypeComposition::create([
                'product_type_id' => $productTypeId,
                'product_composition_id' => $request->product_composition_id,
                'description' => $request->description,
                'is_active' => $request->get('is_active', true),
            ]);

            $composition->load('productComposition');

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المكون بنجاح',
                'data' => new ProductTypeCompositionResource($composition)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المكون',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض مكون معين
     */
    public function show($productTypeId, $compositionId): JsonResponse
    {
        try {
            $composition = ProductTypeComposition::with('productComposition')
                ->where('product_type_id', $productTypeId)
                ->findOrFail($compositionId);

            return response()->json([
                'success' => true,
                'data' => new ProductTypeCompositionResource($composition)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'المكون غير موجود',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * تحديث مكون
     */
    public function update(Request $request, $productTypeId, $compositionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_composition_id' => 'required|exists:product_compositions,id',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $composition = ProductTypeComposition::where('product_type_id', $productTypeId)
                ->findOrFail($compositionId);

            // Check if this composition is already assigned to this product type (excluding current one)
            $existingComposition = ProductTypeComposition::where('product_type_id', $productTypeId)
                ->where('product_composition_id', $request->product_composition_id)
                ->where('id', '!=', $compositionId)
                ->first();

            if ($existingComposition) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا المكون موجود بالفعل لهذا النوع من المنتجات'
                ], 422);
            }

            $composition->update([
                'product_composition_id' => $request->product_composition_id,
                'description' => $request->description,
                'is_active' => $request->get('is_active', true),
            ]);

            $composition->load('productComposition');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المكون بنجاح',
                'data' => new ProductTypeCompositionResource($composition)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المكون',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف مكون
     */
    public function destroy($productTypeId, $compositionId): JsonResponse
    {
        try {
            $composition = ProductTypeComposition::where('product_type_id', $productTypeId)
                ->findOrFail($compositionId);

            $composition->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المكون بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المكون',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تبديل حالة المكون (تفعيل/إلغاء تفعيل)
     */
    public function toggleStatus($productTypeId, $compositionId): JsonResponse
    {
        try {
            $composition = ProductTypeComposition::where('product_type_id', $productTypeId)
                ->findOrFail($compositionId);

            $composition->update([
                'is_active' => !$composition->is_active
            ]);

            $composition->load('productComposition');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة المكون بنجاح',
                'data' => new ProductTypeCompositionResource($composition)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة المكون',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
