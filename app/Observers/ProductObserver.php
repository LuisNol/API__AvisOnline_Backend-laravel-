<?php

namespace App\Observers;

use App\Models\Product\Product;
use Carbon\Carbon;

class ProductObserver
{
    /**
     * Handle the Product "creating" event.
     */
    public function creating(Product $product): void
    {
        // Auto-generar SKU para anuncios
        if (!$product->sku) {
            $product->sku = $this->generateAnuncioSku();
        }

        // Auto-generar slug desde el título
        if (!$product->slug && $product->title) {
            $product->slug = $this->generateSlug($product->title);
        }

        // Establecer fecha de expiración (30 días por defecto)
        if (!$product->expires_at) {
            $product->expires_at = Carbon::now()->addDays(30);
        }

        // Inicializar contador de vistas
        if (!$product->views_count) {
            $product->views_count = 0;
        }
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }

    /**
     * Genera un SKU único para el anuncio
     */
    private function generateAnuncioSku(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Buscar el último SKU del mes para continuar la secuencia
        $lastProduct = Product::where('sku', 'like', "AVO-{$year}{$month}-%")
            ->orderBy('sku', 'desc')
            ->first();
        
        $nextNumber = 1;
        if ($lastProduct) {
            // Extraer el número del último SKU
            $lastSku = $lastProduct->sku;
            $parts = explode('-', $lastSku);
            if (count($parts) === 3) {
                $nextNumber = intval($parts[2]) + 1;
            }
        }
        
        // Generar SKU: AVO-YYYYMM-000001
        return sprintf('AVO-%s%s-%06d', $year, $month, $nextNumber);
    }

    /**
     * Genera un slug único desde el título
     */
    private function generateSlug(string $title): string
    {
        // Convertir a minúsculas y reemplazar espacios/caracteres especiales
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Asegurar que el slug sea único
        $originalSlug = $slug;
        $counter = 1;
        
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
