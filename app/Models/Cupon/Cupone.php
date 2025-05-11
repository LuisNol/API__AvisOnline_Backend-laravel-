<?php

namespace App\Models\Cupon;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cupone extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "code",
        "type_discount",
        "discount",
        "type_count",
        "num_use",
        "state",
        "products",
        "categories",
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
}
