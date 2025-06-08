<?php
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar categorías
$categories = App\Models\Product\Categorie::all(['id', 'name', 'state']);

echo "Total de categorías: " . $categories->count() . PHP_EOL;
echo "Categorías:" . PHP_EOL;

foreach ($categories as $cat) {
    echo "ID: {$cat->id} - {$cat->name} - Estado: {$cat->state}" . PHP_EOL;
}

// También verificar usuario y permisos
$user = App\Models\User::find(1);
if ($user) {
    echo PHP_EOL . "Usuario ID 1: {$user->name}" . PHP_EOL;
    echo "Tipo usuario: {$user->type_user}" . PHP_EOL;
    echo "Roles: " . $user->roles->pluck('name')->implode(', ') . PHP_EOL;
    echo "Permisos: " . $user->getAllPermissions()->pluck('name')->implode(', ') . PHP_EOL;
}
?> 