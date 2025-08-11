<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'role' => $this->role, // Primary display role if you keep it
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'roles' => $this->roles ? $this->roles->pluck('name') : [], // Safe check for roles
            'permissions' => $this->resource->getAllPermissions() ? $this->resource->getAllPermissions()->pluck('name') : [], // Access model via resource property
        ];
    }
}