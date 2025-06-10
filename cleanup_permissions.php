<?php

require 'vendor/autoload.php';

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

// Configurar la base de datos
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LIMPIEZA DE PERMISOS OBSOLETOS ===\n\n";

// 1. Listar permisos actuales
echo "Permisos actuales:\n";
$permissions = Permission::all();
foreach ($permissions as $perm) {
    echo "- {$perm->id}: {$perm->name}\n";
}

// 2. Identificar permisos obsoletos que deben eliminarse
$obsoletePermissions = [
    'users_manage',
    'roles_manage', 
    'permissions_manage',
    'products_manage',
    'categories_manage',
    'brands_manage',
    'coupons_manage',
    'discounts_manage',
    'sliders_manage'
];

echo "\nEliminando permisos obsoletos...\n";

foreach ($obsoletePermissions as $permName) {
    $perm = Permission::where('name', $permName)->first();
    if ($perm) {
        // Primero desconectar de roles
        $perm->roles()->detach();
        $perm->delete();
        echo "✅ Eliminado: {$permName}\n";
    }
}

// 3. Verificar que solo queden los permisos necesarios
$requiredPermissions = [
    'manage-users' => 'Gestión de usuarios',
    'manage-products' => 'Gestión de productos', 
    'manage-own-products' => 'Gestión de productos propios'
];

echo "\nVerificando permisos requeridos...\n";
foreach ($requiredPermissions as $name => $desc) {
    $perm = Permission::where('name', $name)->first();
    if (!$perm) {
        $perm = Permission::create(['name' => $name, 'description' => $desc]);
        echo "✅ Creado: {$name}\n";
    } else {
        echo "✅ Existe: {$name}\n";
    }
}

// 4. Limpiar asignaciones de roles
echo "\nLimpiando roles...\n";

$adminRole = Role::where('name', 'Admin')->first();
$userRole = Role::where('name', 'Usuario')->first();

if ($adminRole) {
    // Admin solo necesita manage-users y manage-products
    $adminPerms = Permission::whereIn('name', ['manage-users', 'manage-products'])->get();
    $adminRole->permissions()->sync($adminPerms->pluck('id')->toArray());
    echo "✅ Admin actualizado con permisos correctos\n";
}

if ($userRole) {
    // Usuario solo necesita manage-own-products
    $userPerms = Permission::where('name', 'manage-own-products')->get();
    $userRole->permissions()->sync($userPerms->pluck('id')->toArray());
    echo "✅ Usuario actualizado con permisos correctos\n";
}

// 5. Verificar usuarios Admin
echo "\nUsuarios con rol Admin:\n";
$adminUsers = User::whereHas('roles', function($q) {
    $q->where('name', 'Admin');
})->get();

foreach ($adminUsers as $user) {
    echo "- {$user->id}: {$user->name} ({$user->email})\n";
}

// 6. Estado final
echo "\n=== ESTADO FINAL ===\n";
echo "Permisos activos:\n";
$finalPermissions = Permission::all();
foreach ($finalPermissions as $perm) {
    echo "- {$perm->name}\n";
}

echo "\nLimpieza completada exitosamente!\n"; 