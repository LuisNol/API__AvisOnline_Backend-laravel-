<?php

namespace App\Models\Sale\Review;

use App\User;
use Carbon\Carbon;
use App\Models\Product\Product;
use App\Models\Sale\SaleDetail;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        "product_id",
        "user_id",
        "sale_detail_id",
        "message",
        "rating"
    ];

    public function setCreatedAtAttribute($value)
    {
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }

    public function setUpdatedAtAttribute($value)
    {
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function sale_detail()
    {
        return $this->belongsTo(SaleDetail::class);
    }
}
