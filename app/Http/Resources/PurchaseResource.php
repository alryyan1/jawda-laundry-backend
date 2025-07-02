<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'reference_number' => $this->reference_number,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date ? $this->purchase_date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}