<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Sale\Review\Review;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discount\DiscountProduct;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "title",
        "categorie_id",
        "slug",
        "sku",
        "tags",
        "price_soles",
        "price_usd",
        "resumen",
        "description",
        "state",
        "imagen",
        "stock",
        "type_inventario",
    ];

    protected $withCount = ['reviews'];

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

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function discountsproducts()
    {
        return $this->hasMany(DiscountProduct::class);
    }

    public function getAvgRatingAttribute()
    {
        return $this->reviews->avg("rating");
    }

    public function getDiscountPAttribute()
    {
        $response = null;
        date_default_timezone_set("America/Lima");
        foreach ($this->discountsproducts as $key => $discounts) {
            if($discounts->discount->state == 1){
                if(Carbon::now()->between($discounts->discount->start_date,$discounts->discount->end_date)){
                    $response = $discounts->discount;
                    break;
                }
            }
        }
        return $response;
    }

    public function getDiscountCAttribute()
    {
        $response = null;
        date_default_timezone_set("America/Lima");
        foreach ($this->categorie->discountcategories as $key => $discounts) {
            if($discounts->discount->state == 1){
                if(Carbon::now()->between($discounts->discount->start_date,$discounts->discount->end_date)){
                    $response = $discounts->discount;
                    break;
                }
            }
        }
        return $response;
    }

    public function scopefilterProduct($query,$search,$categorie_id)
    {
        if($search){
            $query->where("title","like","%".$search."%");
        }
        if($categorie_id){
            $query->where("categorie_id",$categorie_id);
        }
        return $query;
    }

    public function scopefilterAdvance($query,$categories,$review,$min_price,$max_price,$size_id,$color_id,$search_product)
    {
        if($categories && sizeof($categories) > 0){
            $query->whereIn("categorie_id",$categories);
        }
        if($review){
            $query->whereHas("reviews",function($q) use($review) {
                $q->where("rating",$review);
            });
        }
        if($min_price > 0 && $max_price > 0){
            error_log($min_price);
            error_log($max_price);
            $query->whereBetween("price_soles",[$min_price,$max_price]);
        }
        if($size_id){
            $query->whereHas("sizes",function($q) use($size_id) {
                $q->where("name","like","%".$size_id."%");
            });
        }
        if($color_id){
            $query->whereHas("sizes",function($q) use($color_id) {
                $q->whereHas("product_size_colors",function($qt) use($color_id) {
                    $qt->where("product_color_id",$color_id);
                });
            });
        }
        if($search_product){
            $query->where("title","like","%".$search_product."%");
        }
        return $query;
    }
}
