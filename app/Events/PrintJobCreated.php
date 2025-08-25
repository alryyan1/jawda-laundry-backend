<?php

namespace App\Events;

use App\Models\PrintJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\Channel;

class PrintJobCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public PrintJob $printJob;

    public function __construct(PrintJob $printJob)
    {
        $this->printJob = $printJob;
    }

    public function broadcastOn(): array
    {
        return [new Channel('print-jobs')];
    }

    public function broadcastAs(): string
    {
        return 'PrintJobCreated';
    }

    public function broadcastWith(): array
    {
        $orderId = $this->printJob->order_id;
        return [
            'job_id' => $this->printJob->id,
            'order_id' => $orderId,
            'pdf_url' => url("/api/orders/{$orderId}/pos-invoice-pdf"),
        ];
    }
}


