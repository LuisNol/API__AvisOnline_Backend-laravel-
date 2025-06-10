<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->resource->id,
            "name" => $this->resource->name,
            "icon" => $this->resource->icon,
            "imagen" => $this->resource->imagen ? env("APP_URL")."storage/".$this->resource->imagen : NULL,
            "position" => $this->resource->position,
            "state" => $this->resource->state,
            "products_count" => $this->resource->products ? $this->resource->products->count() : 0,
            "created_at" => $this->resource->created_at->format("Y-m-d h:i:s"),
        ];
    }
}
