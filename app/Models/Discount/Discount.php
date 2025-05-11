<?php

namespace App\Models\Discount;

use Carbon\Carbon;
use App\Models\Product\Product;
use App\Models\Product\Categorie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "code",
        "type_discount",
        "discount",
        "state",
        "start_date",
        "end_date",
        "type",// 1 es producto y 2 es categoria
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

    public function products()
    {
        return $this->hasMany(DiscountProduct::class);
    }

    public function categories()
    {
        return $this->hasMany(DiscountCategorie::class);
    }

    public function scopeValidateDiscount($query,$request,$product_array=[],$categorie_array=[])
    {
        $query->where("type",$request->type);
        if($request->type == 1){ //FILTRAR POR PRODUCTO
            $query->whereHas("products",function($q) use($product_array){
                return $q->whereIn("product_id",$product_array);
            });
        }
        if($request->type == 2){//FILTRAR POR CATEGORIA
            $query->whereHas("categories",function($q) use($categorie_array){
                return $q->whereIn("categorie_id",$categorie_array);
            });
        }
        return $query;
    }
}
