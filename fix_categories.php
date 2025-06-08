<?php
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Verificando categorías con datos incompletos..." . PHP_EOL;

// Buscar categorías sin nombre
$corruptedCategories = App\Models\Product\Categorie::whereNull('name')->orWhere('name', '')->get();

echo "Categorías corruptas encontradas: " . $corruptedCategories->count() . PHP_EOL;

foreach ($corruptedCategories as $cat) {
    echo "ID: {$cat->id} - Nombre: '{$cat->name}' - Estado: {$cat->state}" . PHP_EOL;
    
    // Opción 1: Eliminar las categorías corruptas
    echo "Eliminando categoría corrupta ID: {$cat->id}" . PHP_EOL;
    $cat->delete();
}

echo "Verificando categorías después de la limpieza..." . PHP_EOL;
$totalCategories = App\Models\Product\Categorie::count();
echo "Total de categorías válidas: {$totalCategories}" . PHP_EOL;

// Listar categorías válidas
$validCategories = App\Models\Product\Categorie::whereNotNull('name')->where('name', '!=', '')->get(['id', 'name', 'state']);
echo "Categorías válidas:" . PHP_EOL;
foreach ($validCategories as $cat) {
    echo "ID: {$cat->id} - {$cat->name} - Estado: {$cat->state}" . PHP_EOL;
}
?> 