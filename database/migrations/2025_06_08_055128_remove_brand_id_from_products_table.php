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
            // Eliminar la columna brand_id ya que no usaremos marcas en AvisOnline
            $table->dropColumn('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Si necesitamos revertir, agregamos la columna de nuevo
            $table->unsignedBigInteger('brand_id')->nullable()->after('tags');
        });
    }
};
