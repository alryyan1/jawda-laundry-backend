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

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     *Env
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
        return 'order.created';
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
                'customer' => $this->order->customer ? [
                    'id' => $this->order->customer->id,
                    'name' => $this->order->customer->name,
                ] : null,
                'items_count' => $this->order->items->count(),
            ],
            'message' => 'New order created',
            'timestamp' => now()->toISOString(),
        ];
    }
} 