<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\InventoryTransaction;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get all inventory items with pagination and filters
     */
    public function index(Request $request)
    {
        $query = InventoryItem::with(['productType.category', 'supplier'])
            ->orderBy('id', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('productType', function ($pt) use ($search) {
                    $pt->where('name', 'like', "%{$search}%");
                })
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('productType', function ($pt) use ($request) {
                $pt->where('product_category_id', $request->category_id);
            });
        }

        if ($request->filled('low_stock')) {
            $query->where('current_stock', '<=', DB::raw('min_stock_level'));
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * Get low stock items
     */
    public function lowStock()
    {
        $items = $this->inventoryService->getLowStockItems();
        return response()->json($items);
    }

    /**
     * Get inventory item details
     */
    public function show(InventoryItem $inventory)
    {
        $inventory->load(['productType.category', 'supplier', 'transactions' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json($inventory);
    }

    /**
     * Store new inventory item
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_type_id' => 'required|exists:product_types,id|unique:inventory_items,product_type_id',
            'sku' => 'nullable|string|max:100|unique:inventory_items',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json($item, 201);
    }

    /**
     * Update inventory item
     */
    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'product_type_id' => [
                'sometimes',
                'required',
                'exists:product_types,id',
                Rule::unique('inventory_items')->ignore($inventoryItem->id)
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('inventory_items')->ignore($inventoryItem->id)
            ],
            'description' => 'nullable|string',
            'unit' => 'sometimes|required|string|max:50',
            'min_stock_level' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'is_active' => 'sometimes|boolean',
        ]);

        $inventoryItem->update($validated);

        return response()->json($inventoryItem);
    }

    /**
     * Add stock to inventory item
     */
    public function addStock(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->inventoryService->addStock(
                $inventoryItem->id,
                $validated['quantity'],
                $validated['unit_cost'] ?? null,
                'manual',
                null,
                $validated['notes'] ?? null
            );

            $inventoryItem->refresh();
            return response()->json([
                'message' => 'Stock added successfully',
                'item' => $inventoryItem
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Remove stock from inventory item
     */
    public function removeStock(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->inventoryService->removeStock(
                $inventoryItem->id,
                $validated['quantity'],
                'manual',
                null,
                $validated['notes'] ?? null
            );

            $inventoryItem->refresh();
            return response()->json([
                'message' => 'Stock removed successfully',
                'item' => $inventoryItem
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get inventory transactions
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with([
            'inventoryItem.productType.category', 
            'user:id,name'
        ])->orderBy('id', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('inventoryItem.productType', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('notes', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $query->whereHas('inventoryItem.productType', function ($q) use ($request) {
                $q->where('product_category_id', $request->category_id);
            });
        }

        if ($request->filled('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->get('per_page', 50));

        // Transform the data to include productType and category_name for frontend compatibility
        $transactions->getCollection()->transform(function ($transaction) {
            $transaction->productType = $transaction->inventoryItem->productType ?? null;
            $transaction->category_name = $transaction->inventoryItem->productType->category->name ?? null;
            $transaction->created_by = $transaction->user->name ?? null;
            return $transaction;
        });

        return response()->json($transactions);
    }

    /**
     * Create a new inventory transaction
     */
    public function createTransaction(Request $request)
    {
        $validated = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'transaction_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|numeric',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $inventoryItem = InventoryItem::findOrFail($validated['inventory_item_id']);
            
            // Map frontend transaction types to backend types
            $transactionTypeMap = [
                'in' => 'purchase',
                'out' => 'sale',
                'adjustment' => 'adjustment'
            ];
            
            $backendTransactionType = $transactionTypeMap[$validated['transaction_type']];
            $quantity = $validated['quantity'];
            
            // For 'out' transactions, make quantity negative
            if ($validated['transaction_type'] === 'out') {
                $quantity = -abs($quantity);
            }
            
            // For 'out' transactions, check if there's enough stock
            if ($validated['transaction_type'] === 'out' && $inventoryItem->current_stock < abs($quantity)) {
                return response()->json([
                    'message' => 'Insufficient stock. Available: ' . $inventoryItem->current_stock . ', Requested: ' . abs($quantity)
                ], 400);
            }
            
            $unitCost = $validated['unit_price'] ?? $inventoryItem->cost_per_unit ?? 0;
            $totalCost = $quantity * $unitCost;
            
            // Create the transaction
            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $validated['inventory_item_id'],
                'transaction_type' => $backendTransactionType,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => 'manual',
                'reference_id' => null,
                'notes' => $validated['notes'],
                'user_id' => auth()->id(),
            ]);
            
            // Update inventory item stock
            if ($validated['transaction_type'] === 'in') {
                $inventoryItem->addStock(abs($quantity), $unitCost);
            } elseif ($validated['transaction_type'] === 'out') {
                $inventoryItem->removeStock(abs($quantity));
            } else { // adjustment
                if ($quantity > 0) {
                    $inventoryItem->addStock($quantity);
                } else {
                    $inventoryItem->removeStock(abs($quantity));
                }
            }
            
            // Load relationships for response
            $transaction->load(['inventoryItem.productType.category', 'user:id,name']);
            $transaction->productType = $transaction->inventoryItem->productType ?? null;
            $transaction->category_name = $transaction->inventoryItem->productType->category->name ?? null;
            $transaction->created_by = $transaction->user->name ?? null;
            
            return response()->json([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product categories (for inventory filtering)
     */
    public function categories()
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Get inventory statistics
     */
    public function statistics()
    {
        $totalItems = InventoryItem::where('is_active', true)->count();
        $lowStockItems = InventoryItem::where('is_active', true)
            ->where('current_stock', '<=', DB::raw('min_stock_level'))
            ->count();
        $totalValue = $this->inventoryService->getInventoryValue();

        return response()->json([
            'total_items' => $totalItems,
            'low_stock_items' => $lowStockItems,
            'total_value' => $totalValue,
        ]);
    }

    /**
     * Get inventory data for all product types
     */
    public function getProductTypeInventory()
    {
        $inventoryData = InventoryItem::select('product_type_id', 'current_stock', 'unit', 'is_active')
            ->where('is_active', true)
            ->get()
            ->keyBy('product_type_id');

        return response()->json($inventoryData);
    }
} 