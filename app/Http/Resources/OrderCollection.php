<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    public static $wrap = 'data'; // Optional: keep data under 'data' key

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection, // This is OrderResource::collection($this->collection) essentially
            // 'links' => [ // You can customize links if needed
            //     'first' => $this->url(1),
            //     'last' => $this->url($this->lastPage()),
            //     'prev' => $this->previousPageUrl(),
            //     'next' => $this->nextPageUrl(),
            // ],
            // 'meta' => [ // Customize meta if needed
            //     'current_page' => $this->currentPage(),
            //     'from' => $this->firstItem(),
            //     'last_page' => $this->lastPage(),
            //     'path' => $this->path(),
            //     'per_page' => $this->perPage(),
            //     'to' => $this->lastItem(),
            //     'total' => $this->total(),
            // ],
        ];
    }
}