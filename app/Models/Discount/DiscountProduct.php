<?php

namespace App\Models\Discount;

use Carbon\Carbon;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class DiscountProduct extends Model
{
    protected $fillable = [
        "discount_id",
        "product_id",
    ];

    public function setCreatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Lima");
        $this->attributes["created_at"]= Carbon::now();
    }
    public function setUpdatedAtAttribute($value)
    {
    	date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"]= Carbon::now();
    }

    public function discount()
    {
        return  $this->belongsTo(Discount::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
