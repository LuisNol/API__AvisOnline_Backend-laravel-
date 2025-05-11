<?php

namespace App\Models\Sale\WishList;

use App\User;
use Carbon\Carbon;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        "user_id",
        "product_id",
        "product_size_id",
        "product_color_size_id"
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

    public function client()
    {
        return $this->belongsTo(User::class,"user_id");
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
