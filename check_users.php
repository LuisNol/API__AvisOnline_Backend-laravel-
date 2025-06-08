<?php
require_once __DIR__ . '/vendor/autoload.php';

// Cargar configuración de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Verificar usuarios
$users = App\Models\User::all(['id', 'name', 'email', 'type_user']);

echo "Total de usuarios: " . $users->count() . PHP_EOL;
echo "Usuarios:" . PHP_EOL;

foreach ($users as $user) {
    echo "ID: {$user->id} - {$user->name} - {$user->email} - Tipo: {$user->type_user}" . PHP_EOL;
    
    // Verificar roles
    $roles = $user->roles->pluck('name')->implode(', ');
    echo "  Roles: {$roles}" . PHP_EOL;
}

// Verificar si el email de admin existe
$admin = App\Models\User::where('email', 'admin@gmail.com')->first();
if ($admin) {
    echo PHP_EOL . "Usuario admin@gmail.com encontrado: {$admin->name}" . PHP_EOL;
} else {
    echo PHP_EOL . "Usuario admin@gmail.com NO encontrado" . PHP_EOL;
}
?> 