<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'name' => $this->name,
            'expense_category_id' => $this->expense_category_id,
            'description' => $this->description,
            'amount' => (float) $this->amount, // Ensure it's a float in the JSON
            'expense_date' => $this->expense_date ? $this->expense_date->format('Y-m-d') : null, // Consistent date format
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')), // Include user details if loaded
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'payment_method' => $this->payment_method,
        ];
    }
}