<?php

namespace App\Models\Sale;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleAddress extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "sale_id",
        "full_name",
        "full_surname",
        "company_name",
        "county_region",
        "direccion",
        "city",
        "zip_code",
        "phone",
        "email",
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

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
