<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, array $changes = [])
    {
        $this->order = $order;
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pos-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'id' => $this->order->id,
                'daily_order_number' => $this->order->daily_order_number,
                'status' => $this->order->status,
                'total_amount' => $this->order->total_amount,
                'paid_amount' => $this->order->paid_amount,
                'order_date' => $this->order->order_date,
                'customer' => [
                    'id' => $this->order->customer->id,
                    'name' => $this->order->customer->name,
                ],
                'items_count' => $this->order->items->count(),
            ],
            'changes' => $this->changes,
            'message' => 'Order updated',
            'timestamp' => now()->toISOString(),
        ];
    }
} 