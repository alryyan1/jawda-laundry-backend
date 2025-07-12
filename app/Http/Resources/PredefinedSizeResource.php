<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PredefinedSizeResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'product_type_id' => $this->product_type_id,
            'name' => $this->name,
            'length_meters' => (float) $this->length_meters,
            'width_meters' => (float) $this->width_meters,
        ];
    }
}