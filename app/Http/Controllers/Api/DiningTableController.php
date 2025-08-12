<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\TableReservation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DiningTableController extends Controller
{
    /**
     * Display a listing of dining tables with their current status
     */
    public function index(): JsonResponse
    {
        $tables = DiningTable::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($table) {
                // Get active reservation manually
                $activeReservation = $table->reservations()
                    ->where('status', '!=', 'completed')
                    ->where('status', '!=', 'cancelled')
                    ->with('customer')
                    ->latest()
                    ->first();

                // Get active order manually
                $activeOrder = $table->orders()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->with('customer')
                    ->latest()
                    ->first();

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'capacity' => $table->capacity,
                    'status' => $table->status,
                    'description' => $table->description,
                    'is_active' => $table->is_active,
                    'active_reservation' => $activeReservation ? [
                        'id' => $activeReservation->id,
                        'customer_name' => $activeReservation->customer->name,
                        'reservation_date' => $activeReservation->reservation_date,
                        'party_size' => $activeReservation->party_size,
                        'status' => $activeReservation->status,
                        'notes' => $activeReservation->notes,
                    ] : null,
                    'active_order' => $activeOrder ? [
                        'id' => $activeOrder->id,
                        'id' => $activeOrder->id,
                        'daily_order_number' => $activeOrder->daily_order_number,
                        'customer_name' => $activeOrder->customer->name,
                        'status' => $activeOrder->status,
                        'total_amount' => $activeOrder->total_amount,
                        'created_at' => $activeOrder->created_at,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $tables,
        ]);
    }

    /**
     * Store a newly created dining table
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dining_tables,name',
            'capacity' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = DiningTable::create($request->only(['name', 'capacity', 'description']));

        return response()->json([
            'message' => 'Dining table created successfully',
            'data' => $table,
        ], 201);
    }

    /**
     * Display the specified dining table
     */
    public function show(DiningTable $diningTable): JsonResponse
    {
        // Load relationships manually to avoid issues
        $diningTable->load(['reservations.customer', 'orders.customer', 'orders.items']);

        return response()->json([
            'data' => $diningTable,
        ]);
    }

    /**
     * Update the specified dining table
     */
    public function update(Request $request, DiningTable $diningTable): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dining_tables,name,' . $diningTable->id,
            'capacity' => 'required|integer|min:1|max:20',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $diningTable->update($request->only(['name', 'capacity', 'status', 'description', 'is_active']));

        return response()->json([
            'message' => 'Dining table updated successfully',
            'data' => $diningTable,
        ]);
    }

    /**
     * Remove the specified dining table
     */
    public function destroy(DiningTable $diningTable): JsonResponse
    {
        // Check if table has active reservations or orders
        $hasActiveReservations = $diningTable->reservations()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        $hasActiveOrders = $diningTable->orders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($hasActiveReservations || $hasActiveOrders) {
            return response()->json([
                'message' => 'Cannot delete table with active reservations or orders',
            ], 422);
        }

        $diningTable->delete();

        return response()->json([
            'message' => 'Dining table deleted successfully',
        ]);
    }

    /**
     * Update table status
     */
    public function updateStatus(Request $request, DiningTable $diningTable): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,occupied,reserved,maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $diningTable->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Table status updated successfully',
            'data' => $diningTable,
        ]);
    }

    /**
     * Get table statistics
     */
    public function statistics(): JsonResponse
    {
        $totalTables = DiningTable::where('is_active', true)->count();
        $availableTables = DiningTable::where('is_active', true)->where('status', 'available')->count();
        $occupiedTables = DiningTable::where('is_active', true)->where('status', 'occupied')->count();
        $reservedTables = DiningTable::where('is_active', true)->where('status', 'reserved')->count();
        $maintenanceTables = DiningTable::where('is_active', true)->where('status', 'maintenance')->count();

        return response()->json([
            'data' => [
                'total_tables' => $totalTables,
                'available_tables' => $availableTables,
                'occupied_tables' => $occupiedTables,
                'reserved_tables' => $reservedTables,
                'maintenance_tables' => $maintenanceTables,
            ],
        ]);
    }
} 