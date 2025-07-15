<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use App\Http\Resources\RestaurantTableResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RestaurantTable::query();

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active status
        if ($request->has('active_only') && filter_var($request->active_only, FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        // Filter by availability
        if ($request->has('available_only') && filter_var($request->available_only, FILTER_VALIDATE_BOOLEAN)) {
            $query->available();
        }

        // Search by name or number
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('number', 'LIKE', "%{$searchTerm}%");
            });
        }

        $tables = $query->orderBy('number')->get();

        return RestaurantTableResource::collection($tables);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:50|unique:restaurant_tables,number',
            'capacity' => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:available,occupied,reserved,maintenance',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $table = RestaurantTable::create($validatedData);
            return new RestaurantTableResource($table);
        } catch (\Exception $e) {
            Log::error("Error creating restaurant table: " . $e->getMessage());
            return response()->json(['message' => 'Failed to create restaurant table.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RestaurantTable $restaurantTable)
    {
        return new RestaurantTableResource($restaurantTable);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RestaurantTable $restaurantTable)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('restaurant_tables')->ignore($restaurantTable->id),
            ],
            'capacity' => 'sometimes|required|integer|min:1|max:20',
            'description' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|in:available,occupied,reserved,maintenance',
            'is_active' => 'sometimes|boolean',
        ]);

        try {
            $restaurantTable->update($validatedData);
            return new RestaurantTableResource($restaurantTable);
        } catch (\Exception $e) {
            Log::error("Error updating restaurant table {$restaurantTable->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update restaurant table.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RestaurantTable $restaurantTable)
    {
        // Check if table has active orders
        if ($restaurantTable->activeOrders()->exists()) {
            return response()->json(['message' => 'Cannot delete table. It has active orders.'], 409);
        }

        try {
            $restaurantTable->delete();
            return response()->json(['message' => 'Restaurant table deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error("Error deleting restaurant table {$restaurantTable->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete restaurant table.'], 500);
        }
    }

    /**
     * Get available tables for order creation
     */
    public function available()
    {
        $tables = RestaurantTable::available()->orderBy('number')->get();
        return RestaurantTableResource::collection($tables);
    }

    /**
     * Update table status
     */
    public function updateStatus(Request $request, RestaurantTable $restaurantTable)
    {
        $validatedData = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance',
        ]);

        try {
            $restaurantTable->update($validatedData);
            return new RestaurantTableResource($restaurantTable);
        } catch (\Exception $e) {
            Log::error("Error updating table status {$restaurantTable->id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update table status.'], 500);
        }
    }
} 