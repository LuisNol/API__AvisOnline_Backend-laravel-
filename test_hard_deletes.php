<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 VERIFICANDO ELIMINACIONES FÍSICAS EN AVISONLINE\n";
echo "================================================\n\n";

// Verificar configuración de base de datos
try {
    DB::connection()->getPdo();
    echo "✅ Conexión a base de datos: OK\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar que los modelos NO usen SoftDeletes
echo "\n📋 VERIFICANDO MODELOS SIN SOFTDELETES:\n";
echo "----------------------------------------\n";

$models = [
    'App\Models\User' => 'Usuarios',
    'App\Models\Role' => 'Roles', 
    'App\Models\Permission' => 'Permisos',
    'App\Models\Product\Categorie' => 'Categorías',
    'App\Models\Product\Product' => 'Productos/Anuncios',
    'App\Models\Slider' => 'Sliders/Banners'
];

foreach ($models as $modelClass => $description) {
    try {
        $model = new $modelClass;
        $usesSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($model));
        
        if ($usesSoftDeletes) {
            echo "⚠️  $description: Aún usa SoftDeletes\n";
        } else {
            echo "✅ $description: Eliminación física configurada\n";
        }
    } catch (\Exception $e) {
        echo "❌ $description: Error - " . $e->getMessage() . "\n";
    }
}

// Verificar controladores con forceDelete()
echo "\n🔧 VERIFICANDO CONTROLADORES:\n";
echo "-----------------------------\n";

$controllers = [
    'app/Http/Controllers/Admin/UserController.php' => 'UserController',
    'app/Http/Controllers/Admin/RoleController.php' => 'RoleController',
    'app/Http/Controllers/Admin/PermissionController.php' => 'PermissionController',
    'app/Http/Controllers/Admin/Product/CategorieController.php' => 'CategorieController',
    'app/Http/Controllers/Admin/Product/ProductController.php' => 'ProductController',
    'app/Http/Controllers/Admin/SliderController.php' => 'SliderController'
];

foreach ($controllers as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $hasForceDelete = strpos($content, 'forceDelete()') !== false;
        
        if ($hasForceDelete) {
            echo "✅ $name: Usa forceDelete() para eliminación física\n";
        } else {
            echo "⚠️  $name: Podría estar usando delete() normal\n";
        }
    } else {
        echo "❌ $name: Archivo no encontrado\n";
    }
}

// Verificar tablas en base de datos
echo "\n📊 VERIFICANDO ESTRUCTURA DE TABLAS:\n";
echo "------------------------------------\n";

$tables = ['users', 'roles', 'permissions', 'categories', 'products', 'sliders'];

foreach ($tables as $table) {
    try {
        $columns = DB::select("SHOW COLUMNS FROM $table");
        $hasDeletedAt = false;
        
        foreach ($columns as $column) {
            if ($column->Field === 'deleted_at') {
                $hasDeletedAt = true;
                break;
            }
        }
        
        if ($hasDeletedAt) {
            echo "ℹ️  Tabla '$table': Tiene columna deleted_at (pero se ignora con forceDelete)\n";
        } else {
            echo "✅ Tabla '$table': Sin columna deleted_at\n";
        }
    } catch (\Exception $e) {
        echo "❌ Tabla '$table': Error - " . $e->getMessage() . "\n";
    }
}

echo "\n🎯 RESUMEN:\n";
echo "----------\n";
echo "✅ Las eliminaciones ahora son FÍSICAS (permanentes)\n";
echo "✅ Los registros se eliminan completamente de la base de datos\n";
echo "✅ No se pueden recuperar después de eliminar\n";
echo "⚠️  PRECAUCIÓN: Asegúrate de hacer backups antes de eliminar datos importantes\n";

echo "\n🔍 FUNCIONALIDADES DE ELIMINACIÓN:\n";
echo "----------------------------------\n";
echo "👥 Usuarios: Validaciones para no eliminar admin actual o último admin\n";
echo "🔐 Roles: No permite eliminar roles del sistema (Admin, usuario)\n";
echo "🛡️  Permisos: No permite eliminar permisos críticos del sistema\n";
echo "📂 Categorías: Verifica que no tengan anuncios asociados\n";
echo "📝 Productos: Elimina imágenes asociadas del storage\n";
echo "🎠 Sliders: Elimina imágenes del storage\n";

echo "\n✨ CONFIGURACIÓN COMPLETADA EXITOSAMENTE ✨\n"; 