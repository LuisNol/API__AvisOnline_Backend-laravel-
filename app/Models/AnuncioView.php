<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;

class AnuncioView extends Model
{
    use HasFactory;

    protected $table = 'anuncio_views';
    
    protected $fillable = [
        'product_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at'
    ];

    protected $dates = [
        'viewed_at'
    ];

    public function setCreatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["created_at"] = Carbon::now();
    }
    
    public function setUpdatedAtAttribute($value){
        date_default_timezone_set("America/Lima");
        $this->attributes["updated_at"] = Carbon::now();
    }

    // RELACIONES
    public function anuncio() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    // MÉTODOS DE UTILIDAD
    public static function recordView($productId, $userId = null, $ipAddress = null, $userAgent = null) {
        // Evitar contar múltiples vistas del mismo usuario/IP en poco tiempo
        $recentView = self::where('product_id', $productId)
            ->where(function($query) use ($userId, $ipAddress) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('ip_address', $ipAddress);
                }
            })
            ->where('viewed_at', '>=', Carbon::now()->subHours(1)) // No contar si vio en la última hora
            ->first();

        if (!$recentView) {
            // Crear nueva vista
            self::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'viewed_at' => Carbon::now()
            ]);

            // Incrementar contador en el anuncio
            Product::where('id', $productId)->increment('views_count');
        }
    }
}
