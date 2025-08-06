<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderItem;

class OrderItemController extends Controller
{
    public function updateStatus($id, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:50', // You can use Rule::in([...]) for enum
        ]);

        $orderItem = OrderItem::findOrFail($id);
        $orderItem->status = $validated['status'];
        $orderItem->save();

        return response()->json(['message' => 'Order item status updated.', 'order_item' => $orderItem]);
    }

    public function updatePickedUpQuantity($id, Request $request)
    {
        $validated = $request->validate([
            'picked_up_quantity' => 'required|integer|min:0',
        ]);

        $orderItem = OrderItem::findOrFail($id);
        
        // Ensure picked_up_quantity doesn't exceed the total quantity
        if ($validated['picked_up_quantity'] > $orderItem->quantity) {
            return response()->json([
                'message' => 'Picked up quantity cannot exceed the total quantity.',
                'errors' => ['picked_up_quantity' => ['Picked up quantity cannot exceed the total quantity.']]
            ], 422);
        }

        $orderItem->picked_up_quantity = $validated['picked_up_quantity'];
        $orderItem->save();

        return response()->json([
            'message' => 'Order item picked up quantity updated.',
            'order_item' => $orderItem
        ]);
    }

    public function destroy($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        
        // Store order reference before deletion for recalculation
        $order = $orderItem->order;
        
        // Delete the order item
        $orderItem->delete();
        
        // The order total will be automatically recalculated via the model events
        
        return response()->json([
            'message' => 'Order item deleted successfully.',
            'order' => $order->fresh(['customer', 'user', 'items.serviceOffering.productType.category', 'items.serviceOffering.serviceAction', 'diningTable'])
        ]);
    }
} 