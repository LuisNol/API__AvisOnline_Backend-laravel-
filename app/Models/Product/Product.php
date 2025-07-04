<?php

namespace App\Models\Product;

use Carbon\Carbon;
use App\Models\Sale\Review;
use Illuminate\Database\Eloquent\Model;
use App\Models\Discount\DiscountProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        "title",
        "slug",
        "sku",
        "price_pen",
        "imagen",
        "state",
        "description",
        "tags",
        "categorie_first_id",
        "user_id",
        // CAMPOS PARA ANUNCIOS
        "location",
        "contact_phone", 
        "contact_email",
        "expires_at",
        "views_count"
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    public function setUpdatedtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    public function categorie_first(){
        return $this->belongsTo(Categorie::class,"categorie_first_id");
    }



    public function images() {
        return $this->hasMany(ProductImage::class,"product_id");
    }

    public function discount_products() {
        return $this->hasMany(DiscountProduct::class,"product_id");
    }
    
    public function variations() {
        return $this->hasMany(ProductVariation::class,"product_id")->where("product_variation_id",NULL);
    }

    public function specifications() {
        return $this->hasMany(ProductSpecification::class,"product_id");
    }

    public function reviews() {
        return $this->hasMany(Review::class,"product_id");
    }

    // NUEVAS RELACIONES PARA ANUNCIOS
    public function anuncio_views() {
        return $this->hasMany(\App\Models\AnuncioView::class,"product_id");
    }

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getReviewsCountAttribute() {
        return $this->reviews->count();
    }

    public function getReviewsAvgAttribute() {
        return $this->reviews->avg("rating");
    }

    // discount_categorie
    public function getDiscountCategorieAttribute() {
        date_default_timezone_set("America/Lima");
        $discount = null;
        foreach ($this->categorie_first->discount_categories as $key => $discount_categorie) {
            if($discount_categorie->discount && $discount_categorie->discount->type_campaing == 1 &&
            $discount_categorie->discount->state == 1){
                // [24-01-2024, 25  ,27-01-2024]
                if(Carbon::now()->between($discount_categorie->discount->start_date,Carbon::parse(
                    $discount_categorie->discount->end_date)->addDays(1))){
                    $discount = $discount_categorie->discount;
                    break;
                }
            }
        }
        return $discount;
    }

    public function getDiscountProductAttribute() {
        date_default_timezone_set("America/Lima");
        $discount = null;
        foreach ($this->discount_products as $key => $discount_product) {
            if($discount_product->discount && $discount_product->discount->type_campaing == 1 &&
            $discount_product->discount->state == 1){
                // [24-01-2024, 25  ,27-01-2024]
                if(Carbon::now()->between($discount_product->discount->start_date,Carbon::parse(
                    $discount_product->discount->end_date)->addDays(1))){
                    $discount = $discount_product->discount;
                    break;
                }
            }
        }
        return $discount;
    }



    public function scopeFilterAdvanceProduct($query,$search,$categorie_id){                 
        if($search){
            $query->where("title","like","%".$search."%");
        }
        if($categorie_id){
            $query->where("categorie_first_id",$categorie_id);
        }
        return $query;
    }

    public function scopefilterAdvanceEcommerce($query,$categories_selected,$colors_product_selected,
    $brands_selected,$min_price,$max_price,$currency,$product_general_ids_array,$options_aditional,$search){

        if($categories_selected && sizeof($categories_selected) > 0){
            $query->whereIn("categorie_first_id",$categories_selected);
        }

        if($colors_product_selected && sizeof($colors_product_selected) > 0){
            // $query->whereHas("variations",function($q) use($colors_selected) {
            //     $q->whereHas("propertie",function($subq) use($colors_selected) {
            //         error_log(json_encode($colors_selected));
            //         $subq->whereIn("code",$colors_selected);
            //     });
            // });
                $query->whereIn("id",$colors_product_selected);
            // $query->whereHas("variations",function($q) use($colors_selected) {
            //     $q->whereHas("variation_children",function($sub) use($colors_selected) {
            //         $sub->whereHas("propertie",function($subq) use($colors_selected) {
            //             $subq->whereIn("code",$colors_selected);
            //         });
            //     });
            // });
        }

        if($brands_selected && sizeof($brands_selected) > 0){
            $query->whereIn("brand_id",$brands_selected);
        }

        if($min_price > 0 && $max_price > 0){
            if($currency == "PEN"){
                $query->whereBetween("price_pen",[$min_price,$max_price]);
            }
            if($currency == "USD"){
                $query->whereBetween("price_usd",[$min_price,$max_price]);
            }
        }
        if($product_general_ids_array && sizeof($product_general_ids_array) > 0){
            $query->whereIn("id",$product_general_ids_array);
        }
        if($options_aditional && sizeof($options_aditional) > 0 && in_array("review",$options_aditional)){
            $query->has("reviews");
        }

        if($search){
            $query->where("title","like","%".$search."%");
        }
        return $query;
    }
}
