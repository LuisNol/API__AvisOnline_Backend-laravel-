<?php

namespace App\Http\Resources\Product;

use App\Helpers\UrlHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            "title" => $this->resource->title,
            "slug"  => $this->resource->slug,
            "sku" => $this->resource->sku,
            "price_pen"  => $this->resource->price_pen,
            "imagen"  => UrlHelper::getStorageUrl($this->resource->imagen),
            "state"  => $this->resource->state,
            "description"  => $this->resource->description,
            "tags"  => $this->resource->tags ? json_decode($this->resource->tags) : [],
            
            // CATEGORÍA (SOLO PRIMER NIVEL)
            "categorie_first_id"  => $this->resource->categorie_first_id,
            "categorie_first"  => $this->resource->categorie_first ? [
                "id" => $this->resource->categorie_first->id,
                "name" => $this->resource->categorie_first->name, 
            ] : NULL,
            
            // CAMPOS ESPECÍFICOS PARA ANUNCIOS
            "location" => $this->resource->location,
            "contact_phone" => $this->resource->contact_phone,
            "contact_email" => $this->resource->contact_email,
            "expires_at" => $this->resource->expires_at,
            "views_count" => $this->resource->views_count,
            "user_id" => $this->resource->user_id,
            
            // INFORMACIÓN DEL VENDEDOR/USUARIO
            "user" => $this->resource->user ? [
                "id" => $this->resource->user->id,
                "name" => $this->resource->user->name,
                "surname" => $this->resource->user->surname,
                "email" => $this->resource->user->email,
                "phone" => $this->resource->user->phone,
                "avatar" => $this->resource->user->avatar 
                    ? UrlHelper::getStorageUrl($this->resource->user->avatar)
                    : null,
                "bio" => $this->resource->user->bio,
                "address_city" => $this->resource->user->address_city,
                "member_since" => $this->resource->user->created_at->format("Y-m-d h:i:s"),
            ] : null,
            
            "created_at" => $this->resource->created_at->format("Y-m-d h:i:s"),
            "images" => $this->resource->images->map(function($image) {
                return [
                    "id" => $image->id,
                    "imagen" => UrlHelper::getStorageUrl($image->imagen),
                ];
            })
        ];
    }
}
