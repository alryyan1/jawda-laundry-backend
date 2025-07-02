<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'amount' => (float) $this->amount,
            'method' => $this->method,
            'type' => $this->type,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'payment_date' => $this->payment_date->toIso8601String(),
        ];
    }
}