<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductComposition;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductCompositionController extends Controller
{
    /**
     * عرض جميع المكونات الأساسية
     */
    public function index(): JsonResponse
    {
        try {
            $compositions = ProductComposition::orderBy('id', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $compositions
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
     * إنشاء مكون أساسي جديد
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:product_compositions,name',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $composition = ProductComposition::create([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المكون بنجاح',
                'data' => $composition
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
     * عرض مكون أساسي معين
     */
    public function show($id): JsonResponse
    {
        try {
            $composition = ProductComposition::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $composition
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
     * تحديث مكون أساسي
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:product_compositions,name,' . $id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $composition = ProductComposition::findOrFail($id);
            $composition->update([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المكون بنجاح',
                'data' => $composition
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
     * حذف مكون أساسي
     */
    public function destroy($id): JsonResponse
    {
        try {
            $composition = ProductComposition::findOrFail($id);

            // Check if this composition is being used by any product types
            if ($composition->productTypeCompositions()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذا المكون لأنه مستخدم في أنواع منتجات أخرى'
                ], 422);
            }

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
}
