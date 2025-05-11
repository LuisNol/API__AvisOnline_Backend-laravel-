<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CartShopCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "data" => CartShopResource::collection($this->collection),
        ];
    }
}
