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
} 