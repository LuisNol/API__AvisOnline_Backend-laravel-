<?php

require 'vendor/autoload.php';

use App\Models\User;
use App\Models\Role;

// Configurar la base de datos
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DE PERMISOS DE USUARIOS ===\n\n";

// Buscar usuarios administradores
$adminUsers = User::whereHas('roles', function($q) {
    $q->where('name', 'Admin');
})->get();

echo "Usuarios con rol Admin:\n";
foreach ($adminUsers as $user) {
    echo "- {$user->id}: {$user->name} ({$user->email})\n";
    $userRoles = $user->roles()->with('permissions')->get();
    foreach ($userRoles as $role) {
        echo "  Rol: {$role->name}\n";
        foreach ($role->permissions as $perm) {
            echo "    Permiso: {$perm->name}\n";
        }
    }
    
    // Verificar específicamente el permiso de manage-users
    $hasManageUsers = $user->hasPermission('manage-users');
    echo "  ¿Puede gestionar usuarios? " . ($hasManageUsers ? "SÍ" : "NO") . "\n\n";
}

// Verificar que el rol Admin tenga el permiso manage-users
$adminRole = Role::where('name', 'Admin')->first();
if ($adminRole) {
    $manageUsersInRole = $adminRole->permissions()->where('name', 'manage-users')->exists();
    echo "¿Rol Admin tiene permiso manage-users? " . ($manageUsersInRole ? "SÍ" : "NO") . "\n";
}

echo "\n¡Verificación completada!\n"; 