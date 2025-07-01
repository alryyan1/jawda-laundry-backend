<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Resources\SupplierResource;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    public function __construct()
    {
        // Protect methods using Spatie permissions. Define these in your seeder.
        $this->middleware('can:supplier:list')->only('index', 'all');
        $this->middleware('can:supplier:create')->only('store');
        $this->middleware('can:supplier:update')->only('update');
        $this->middleware('can:supplier:delete')->only('destroy');
    }

    /**
     * Display a paginated listing of the suppliers.
     */
    public function index(Request $request)
    {
        $query = Supplier::withCount('purchases')->latest();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('contact_person', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('phone', 'LIKE', "%{$searchTerm}%");
            });
        }

        $suppliers = $query->paginate($request->get('per_page', 15));
        return SupplierResource::collection($suppliers);
    }
    
    /**
     * Fetch all suppliers for select dropdowns (non-paginated).
     */
    public function all()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return SupplierResource::collection($suppliers);
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|string|email|max:255|unique:suppliers,email',
            'address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $supplier = Supplier::create($validatedData);
            return new SupplierResource($supplier);
        } catch (\Exception $e) {
            Log::error("Error creating supplier: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create supplier.'], 500);
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load('purchases');
        return new SupplierResource($supplier);
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validatedData = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'contact_person' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'address' => 'sometimes|nullable|string|max:1000',
            'notes' => 'sometimes|nullable|string|max:2000',
        ]);

        try {
            $supplier->update($validatedData);
            return new SupplierResource($supplier);
        } catch (\Exception $e) {
            Log::error("Error updating supplier {$supplier->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update supplier.'], 500);
        }
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier)
    {
        // Prevent deletion if the supplier has associated purchases
        if ($supplier->purchases()->exists()) {
            return response()->json([
                'message' => 'Cannot delete supplier with existing purchases. Please reassign or delete the purchases first.'
            ], 409); // 409 Conflict
        }

        try {
            $supplier->delete();
            return response()->json(['message' => 'Supplier deleted successfully.']);
        } catch (\Exception $e) {
            Log::error("Error deleting supplier {$supplier->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete supplier.'], 500);
        }
    }
}