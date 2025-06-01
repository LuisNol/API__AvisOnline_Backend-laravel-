<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el permiso para gestionar productos propios
        $manageOwnProductsPermission = Permission::firstOrCreate(
            ['name' => 'manage-own-products'],
            ['description' => 'Puede gestionar solo sus propios productos']
        );
        
        // Crear el rol de Usuario
        $userRole = Role::firstOrCreate(
            ['name' => 'Usuario'],
            ['description' => 'Usuario que solo puede gestionar sus propios productos']
        );
        
        // Asignar permiso al rol
        $userRole->permissions()->syncWithoutDetaching([$manageOwnProductsPermission->id]);
        
        $this->command->info('Rol de Usuario creado con permiso para gestionar productos propios');
    }
} 