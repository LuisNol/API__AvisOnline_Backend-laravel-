<?php

namespace App\Http\Resources\Ecommerce\Sale;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SaleOCollection extends ResourceCollection
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
            "data" => SaleOResource::collection($this->collection),
        ];
    }
}
