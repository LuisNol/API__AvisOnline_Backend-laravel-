<?php

namespace App\Models\Client;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddressUser extends Model
{
    use SoftDeletes;
    protected $fillable = [
        "user_id",
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
