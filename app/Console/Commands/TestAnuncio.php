<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product\Product;
use App\Models\AnuncioView;

class TestAnuncio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:anuncio';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la funcionalidad de anuncios de AvisOnline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Probando funcionalidad de AvisOnline...');
        
        // 1. Crear un anuncio de prueba
        $this->info('📝 Creando anuncio de prueba...');
        
        $anuncio = new Product();
        $anuncio->title = 'iPhone 13 Pro Max - Como nuevo';

        $anuncio->imagen = 'placeholder.jpg';
        $anuncio->description = 'Vendo mi iPhone 13 Pro Max de 256GB, color azul sierra.';
        $anuncio->categorie_first_id = 1;
        $anuncio->brand_id = 1; // Por ahora agregamos este campo requerido
        $anuncio->user_id = 1;
        $anuncio->price_pen = 3500;
        $anuncio->location = 'Lima, Miraflores';
        $anuncio->contact_phone = '+51 987654321';
        $anuncio->contact_email = 'vendedor@ejemplo.com';
        $anuncio->state = 1;
        $anuncio->tags = 'iPhone,celular,smartphone,apple';
        
        $anuncio->save();
        
        $this->info("✅ Anuncio creado exitosamente!");
        $this->info("   ID: {$anuncio->id}");
        $this->info("   SKU: {$anuncio->sku}");
        $this->info("   Título: {$anuncio->title}");
        $this->info("   Ubicación: {$anuncio->location}");
        $this->info("   Expira: {$anuncio->expires_at}");
        $this->info("   Vistas: {$anuncio->views_count}");
        
        // 2. Simular una vista
        $this->info('👀 Simulando vista del anuncio...');
        
        AnuncioView::recordView(
            $anuncio->id, 
            null, // Usuario anónimo
            '192.168.1.100', 
            'Mozilla/5.0 Test Browser'
        );
        
        // Refrescar datos
        $anuncio->refresh();
        $this->info("✅ Vista registrada! Contador: {$anuncio->views_count}");
        
        // 3. Mostrar estadísticas
        $totalAnuncios = Product::count();
        $totalVistas = AnuncioView::count();
        
        $this->info('📊 Estadísticas:');
        $this->info("   Total anuncios: {$totalAnuncios}");
        $this->info("   Total vistas: {$totalVistas}");
        
        $this->info('🎉 ¡Prueba completada exitosamente!');
        
        return 0;
    }
}
