<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product\Product;
use App\Observers\ProductObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observer para auto-generar SKUs en anuncios
        Product::observe(ProductObserver::class);
    }
}
