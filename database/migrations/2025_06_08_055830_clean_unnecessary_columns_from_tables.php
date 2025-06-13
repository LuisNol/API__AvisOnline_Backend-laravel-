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
        // Limpiar tabla PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            // Eliminar columnas innecesarias del e-commerce
            $table->dropColumn([
                'price_usd',            // No usamos USD en anuncios locales
                'stock',                // Los anuncios no manejan inventario
                'categorie_second_id',  // Subcategorías innecesarias
                'categorie_third_id'    // Subcategorías innecesarias
            ]);
        });

        // Limpiar tabla CATEGORIES  
        Schema::table('categories', function (Blueprint $table) {
            // Eliminar columnas de subcategorías innecesarias
            $table->dropColumn([
                'type_categorie',       // Campo innecesario
                'categorie_second_id',  // Subcategorías innecesarias
                'categorie_third_id'    // Subcategorías innecesarias
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en PRODUCTS
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_usd', 10, 2)->nullable()->after('price_pen');
            $table->integer('stock')->nullable()->after('tags');
            $table->unsignedBigInteger('categorie_second_id')->nullable()->after('categorie_first_id');
            $table->unsignedBigInteger('categorie_third_id')->nullable()->after('categorie_second_id');
        });

        // Revertir cambios en CATEGORIES
        Schema::table('categories', function (Blueprint $table) {
            $table->tinyInteger('type_categorie')->unsigned()->default(1)->after('position');
            $table->unsignedBigInteger('categorie_second_id')->nullable()->after('imagen');
            $table->unsignedBigInteger('categorie_third_id')->nullable()->after('categorie_second_id');
        });
    }
};
