<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Http\Resources\PurchaseResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;


class PurchaseController extends Controller
{

    public function __construct()
    {
        // Authorization middleware removed
    }

    /**
     * Display a paginated listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Purchase::with(['supplier:id,name', 'user:id,name'])->latest('purchase_date');

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->where('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('purchase_date', '<=', $request->date_to);
        }

        $purchases = $query->paginate($request->get('per_page', 15));
        return PurchaseResource::collection($purchases);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'reference_number' => 'nullable|string|max:255|unique:purchases,reference_number',
            'status' => ['required', Rule::in(['ordered', 'received', 'paid', 'partially_paid', 'cancelled'])],
            'purchase_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_type_id' => 'required|integer|exists:product_types,id',
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $totalAmount = 0;
        $itemsToCreate = [];

        foreach ($validatedData['items'] as $item) {
            $quantity = $item['quantity'];
            $unitPrice = $item['unit_price'];
            $subTotal = $quantity * $unitPrice;
            $totalAmount += $subTotal;
            $itemsToCreate[] = $item + ['sub_total' => $subTotal];
        }

        DB::beginTransaction();
        try {
            $purchase = Purchase::create([
                'supplier_id' => $validatedData['supplier_id'],
                'reference_number' => $validatedData['reference_number'] ?? null,
                'total_amount' => $totalAmount,
                'status' => $validatedData['status'],
                'purchase_date' => $validatedData['purchase_date'],
                'notes' => $validatedData['notes'] ?? null,
                'user_id' => Auth::id(),
            ]);

            $purchase->items()->createMany($itemsToCreate);

            DB::commit();
            $purchase->load(['supplier', 'user', 'items']);
            
            return new PurchaseResource($purchase);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating purchase: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create purchase.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'user', 'items']);
        return new PurchaseResource($purchase);
    }

    /**
     * Update the specified resource in storage.
     * Note: A full update for purchases with items can be complex.
     * This is a simplified version updating only top-level fields.
     */
    public function update(Request $request, Purchase $purchase)
    {
         $validatedData = $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'reference_number' => ['sometimes','nullable','string','max:255', Rule::unique('purchases')->ignore($purchase->id)],
            'status' => ['sometimes','required', Rule::in(['ordered', 'received', 'paid', 'partially_paid', 'cancelled'])],
            'purchase_date' => 'sometimes|required|date_format:Y-m-d',
            'notes' => 'sometimes|nullable|string|max:2000',
            // Add validation for updating items if you implement that logic
        ]);

        try {
            $purchase->update($validatedData);
            // If items were passed, you'd implement the delete-and-recreate logic here
            // similar to the OrderController@update method.

            $purchase->load(['supplier', 'user', 'items']);
            return new PurchaseResource($purchase);

        } catch (\Exception $e) {
            Log::error("Error updating purchase {$purchase->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update purchase.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();
        try {
            // Associated items will be deleted automatically due to onDelete('cascade')
            $purchase->delete();
            DB::commit();
            return response()->json(['message' => 'Purchase deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting purchase {$purchase->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete purchase.'], 500);
        }
    }


}