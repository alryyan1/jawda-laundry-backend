<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\TableReservation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TableReservationController extends Controller
{
    /**
     * Display a listing of table reservations
     */
    public function index(Request $request): JsonResponse
    {
        $query = TableReservation::with(['diningTable', 'customer', 'order']);

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('reservation_date', $request->date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by table
        if ($request->has('table_id')) {
            $query->where('dining_table_id', $request->table_id);
        }

        $reservations = $query->orderBy('reservation_date', 'desc')->paginate(20);

        return response()->json($reservations);
    }

    /**
     * Store a newly created table reservation
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dining_table_id' => 'required|exists:dining_tables,id',
            'customer_id' => 'required|exists:customers,id',
            'reservation_date' => 'required|date|after:now',
            'party_size' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string',
            'contact_phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if table is available for the requested time
        $table = DiningTable::find($request->dining_table_id);
        if (!$table->isAvailable()) {
            return response()->json([
                'message' => 'Table is not available for reservation',
            ], 422);
        }

        // Check for conflicting reservations
        $conflictingReservation = TableReservation::where('dining_table_id', $request->dining_table_id)
            ->where('status', '!=', 'cancelled')
            ->where('reservation_date', $request->reservation_date)
            ->exists();

        if ($conflictingReservation) {
            return response()->json([
                'message' => 'Table is already reserved for this time',
            ], 422);
        }

        // Update table status to reserved
        $table->update(['status' => 'reserved']);

        $reservation = TableReservation::create($request->all());

        return response()->json([
            'message' => 'Reservation created successfully',
            'data' => $reservation->load(['diningTable', 'customer']),
        ], 201);
    }

    /**
     * Display the specified table reservation
     */
    public function show(TableReservation $tableReservation): JsonResponse
    {
        Log::info('TableReservation show method called for ID: ' . $tableReservation->id);
        
        $tableReservation->load(['diningTable', 'customer', 'order']);

        return response()->json([
            'data' => $tableReservation,
        ]);
    }

    /**
     * Update the specified table reservation
     */
    public function update(Request $request, TableReservation $tableReservation): JsonResponse
    {
        Log::info('TableReservation update method called for ID: ' . $tableReservation->id);
        
        $validator = Validator::make($request->all(), [
            'dining_table_id' => 'sometimes|required|exists:dining_tables,id',
            'customer_id' => 'sometimes|required|exists:customers,id',
            'reservation_date' => 'sometimes|required|date',
            'party_size' => 'sometimes|required|integer|min:1|max:20',
            'status' => 'sometimes|required|in:confirmed,seated,completed,cancelled',
            'notes' => 'nullable|string',
            'contact_phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Handle status changes
        if ($request->has('status')) {
            $oldStatus = $tableReservation->status;
            $newStatus = $request->status;

            // If changing from seated to completed, update table status
            if ($oldStatus === 'seated' && $newStatus === 'completed') {
                $tableReservation->diningTable->update(['status' => 'available']);
            }

            // If changing to seated, update table status
            if ($newStatus === 'seated') {
                $tableReservation->diningTable->update(['status' => 'occupied']);
            }

            // If cancelling, update table status
            if ($newStatus === 'cancelled') {
                $tableReservation->diningTable->update(['status' => 'available']);
            }
        }

        $tableReservation->update($request->all());

        return response()->json([
            'message' => 'Reservation updated successfully',
            'data' => $tableReservation->load(['diningTable', 'customer', 'order']),
        ]);
    }

    /**
     * Remove the specified table reservation
     */
    public function destroy(TableReservation $tableReservation): JsonResponse
    {
        Log::info('TableReservation destroy method called for ID: ' . $tableReservation->id);
        
        // Update table status if reservation is active
        if (!in_array($tableReservation->status, ['completed', 'cancelled'])) {
            $tableReservation->diningTable->update(['status' => 'available']);
        }

        $tableReservation->delete();

        return response()->json([
            'message' => 'Reservation deleted successfully',
        ]);
    }

    /**
     * Assign order to reservation
     */
    public function assignOrder(Request $request, TableReservation $tableReservation): JsonResponse
    {
        Log::info('TableReservation assignOrder method called for ID: ' . $tableReservation->id);
        
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::find($request->order_id);

        // Update the order with the dining table
        $order->update(['dining_table_id' => $tableReservation->dining_table_id]);

        // Update the reservation with the order
        $tableReservation->update(['order_id' => $request->order_id]);

        // Update table status to occupied
        $tableReservation->diningTable->update(['status' => 'occupied']);

        return response()->json([
            'message' => 'Order assigned to reservation successfully',
            'data' => $tableReservation->load(['diningTable', 'customer', 'order']),
        ]);
    }

    /**
     * Get today's reservations
     */
    public function todayReservations(): JsonResponse
    {
        $reservations = TableReservation::with(['diningTable', 'customer', 'order'])
            ->whereDate('reservation_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('reservation_date')
            ->get();

        return response()->json([
            'data' => $reservations,
        ]);
    }
} 