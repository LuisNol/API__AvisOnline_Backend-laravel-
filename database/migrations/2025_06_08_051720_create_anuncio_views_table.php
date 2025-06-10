<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anuncio_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // ID del anuncio (products)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Usuario que vio (opcional)
            $table->string('ip_address', 45)->nullable(); // IP del visitante
            $table->string('user_agent')->nullable(); // Navegador del visitante
            $table->timestamp('viewed_at')->default(now()); // Cuándo fue visto
            $table->timestamps();
            
            // ÍNDICES PARA RENDIMIENTO
            $table->index(['product_id', 'viewed_at']); // Para contar vistas por anuncio
            $table->index(['user_id', 'product_id']); // Para evitar doble conteo por usuario
            $table->index(['ip_address', 'product_id']); // Para evitar spam de IPs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anuncio_views');
    }
};
