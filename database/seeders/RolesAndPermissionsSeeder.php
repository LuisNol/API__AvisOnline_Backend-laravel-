<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos
        $permissions = [
            'users_manage' => 'Gestionar usuarios',
            'roles_manage' => 'Gestionar roles',
            'permissions_manage' => 'Gestionar permisos',
            'products_manage' => 'Gestionar productos',
            'categories_manage' => 'Gestionar categorías',
            'sales_manage' => 'Gestionar ventas',
            'brands_manage' => 'Gestionar marcas',
            'sliders_manage' => 'Gestionar sliders',
            'coupons_manage' => 'Gestionar cupones',
            'discounts_manage' => 'Gestionar descuentos',
        ];
        
        $createdPermissions = [];
        
        foreach ($permissions as $permName => $description) {
            $permission = Permission::create([
                'name' => $permName,
                'description' => $description
            ]);
            $createdPermissions[] = $permission->id;
            echo "Permiso creado: $permName\n";
        }
        
        // Crear rol de administrador
        $adminRole = Role::create([
            'name' => 'Admin',
            'description' => 'Administrador con todos los permisos'
        ]);
        
        echo "Rol creado: Admin\n";
        
        // Asignar todos los permisos al rol admin
        $adminRole->permissions()->attach($createdPermissions);
        
        echo "Permisos asignados al rol Admin\n";
        
        // Asignar rol admin al usuario administrador
        $admin = User::where('email', 'admin@sistema.com')->first();
        if ($admin) {
            $admin->roles()->attach($adminRole->id);
            echo "Rol Admin asignado al usuario administrador\n";
        } else {
            echo "ADVERTENCIA: No se encontró el usuario administrador para asignar roles\n";
        }
        
        // Asignar rol admin al usuario predeterminado
        $defaultUser = User::where('email', 'echodev@gmail.com')->first();
        if ($defaultUser) {
            $defaultUser->roles()->attach($adminRole->id);
            echo "Rol Admin asignado al usuario predeterminado\n";
        } else {
            echo "ADVERTENCIA: No se encontró el usuario predeterminado para asignar roles\n";
        }
    }
} 