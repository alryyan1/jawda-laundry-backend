<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
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
            'purchase_id' => $this->purchase_id,
            'item_name' => $this->item_name,
            'description' => $this->description,
            'quantity' => (int) $this->quantity,
            'unit' => $this->unit,
            'unit_price' => (float) $this->unit_price,
            'sub_total' => (float) $this->sub_total,
        ];
    }
}