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
        Schema::table('products', function (Blueprint $table) {
            // CAMPOS NUEVOS PARA ANUNCIOS
            $table->string('location')->nullable()->after('user_id'); // Ubicación del anuncio
            $table->string('contact_phone')->nullable()->after('location'); // Teléfono de contacto
            $table->string('contact_email')->nullable()->after('contact_phone'); // Email alternativo
            $table->timestamp('expires_at')->nullable()->after('contact_email'); // Fecha de expiración
            $table->integer('views_count')->default(0)->after('expires_at'); // Contador de vistas
            
            // MODIFICAR CAMPOS EXISTENTES
            $table->decimal('price_pen', 10, 2)->nullable()->change(); // Hacer precio opcional
            $table->decimal('price_usd', 10, 2)->nullable()->change(); // Hacer precio opcional
            $table->string('sku')->nullable()->change(); // SKU será auto-generado
            $table->integer('stock')->nullable()->change(); // Stock opcional para anuncios
            
            // NUEVOS ÍNDICES PARA RENDIMIENTO
            $table->index(['user_id', 'state']); // Para buscar anuncios por usuario
            $table->index(['location']); // Para búsquedas por ubicación
            $table->index(['expires_at']); // Para limpiar anuncios expirados
            $table->index(['views_count']); // Para ordenar por popularidad
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ELIMINAR CAMPOS NUEVOS
            $table->dropColumn([
                'location', 
                'contact_phone', 
                'contact_email', 
                'expires_at', 
                'views_count'
            ]);
            
            // REVERTIR CAMBIOS EN CAMPOS EXISTENTES
            $table->decimal('price_pen', 10, 2)->nullable(false)->change();
            $table->decimal('price_usd', 10, 2)->nullable(false)->change();
            $table->string('sku')->nullable(false)->change();
            $table->integer('stock')->nullable(false)->change();
            
            // ELIMINAR ÍNDICES
            $table->dropIndex(['user_id', 'state']);
            $table->dropIndex(['location']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['views_count']);
        });
    }
};
