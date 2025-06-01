<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VerifyProductsPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify product-related permissions and roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando permisos relacionados con productos...');
        
        // Verificar si los permisos existen
        $this->info('Permisos existentes:');
        $permissions = Permission::all();
        $table = [];
        foreach ($permissions as $permission) {
            $table[] = [
                'ID' => $permission->id,
                'Nombre' => $permission->name,
                'Descripción' => $permission->description
            ];
        }
        $this->table(['ID', 'Nombre', 'Descripción'], $table);
        
        // Verificar si los permisos manage-products y manage-own-products existen
        $manageProducts = Permission::where('name', 'manage-products')->first();
        $manageOwnProducts = Permission::where('name', 'manage-own-products')->first();
        
        if (!$manageProducts) {
            $this->error('Permiso manage-products no encontrado. Este es necesario para administradores.');
        } else {
            $this->info('Permiso manage-products encontrado (ID: ' . $manageProducts->id . ')');
        }
        
        if (!$manageOwnProducts) {
            $this->error('Permiso manage-own-products no encontrado. Este es necesario para usuarios regulares.');
        } else {
            $this->info('Permiso manage-own-products encontrado (ID: ' . $manageOwnProducts->id . ')');
        }
        
        // Verificar roles
        $this->info('Roles existentes:');
        $roles = Role::all();
        $roleTable = [];
        foreach ($roles as $role) {
            $permsCount = $role->permissions()->count();
            $roleTable[] = [
                'ID' => $role->id,
                'Nombre' => $role->name,
                'Descripción' => $role->description,
                'Permisos' => $permsCount
            ];
        }
        $this->table(['ID', 'Nombre', 'Descripción', 'Permisos'], $roleTable);
        
        // Verificar permisos asociados a roles
        $adminRole = Role::where('name', 'Admin')->first();
        $userRole = Role::where('name', 'Usuario')->first();
        
        if ($adminRole) {
            $this->info('Permisos del rol Admin:');
            $adminPerms = $adminRole->permissions;
            $adminPermsTable = [];
            foreach ($adminPerms as $perm) {
                $adminPermsTable[] = [
                    'ID' => $perm->id,
                    'Nombre' => $perm->name
                ];
            }
            $this->table(['ID', 'Nombre'], $adminPermsTable);
            
            // Verificar si Admin tiene los permisos necesarios
            $hasManageProducts = $adminRole->permissions()->where('name', 'manage-products')->exists();
            $hasManageOwnProducts = $adminRole->permissions()->where('name', 'manage-own-products')->exists();
            
            if (!$hasManageProducts) {
                $this->error('El rol Admin no tiene el permiso manage-products.');
            }
            
            if (!$hasManageOwnProducts) {
                $this->warn('El rol Admin no tiene el permiso manage-own-products (opcional para Admin).');
            }
        } else {
            $this->error('Rol Admin no encontrado.');
        }
        
        if ($userRole) {
            $this->info('Permisos del rol Usuario:');
            $userPerms = $userRole->permissions;
            $userPermsTable = [];
            foreach ($userPerms as $perm) {
                $userPermsTable[] = [
                    'ID' => $perm->id,
                    'Nombre' => $perm->name
                ];
            }
            $this->table(['ID', 'Nombre'], $userPermsTable);
            
            // Verificar si Usuario tiene el permiso necesario
            $hasManageOwnProducts = $userRole->permissions()->where('name', 'manage-own-products')->exists();
            
            if (!$hasManageOwnProducts) {
                $this->error('El rol Usuario no tiene el permiso manage-own-products.');
            }
        } else {
            $this->error('Rol Usuario no encontrado.');
        }
        
        // Verificar usuarios
        $this->info('Usuarios con el rol Usuario:');
        $usersWithUserRole = User::whereHas('roles', function($q) {
            $q->where('name', 'Usuario');
        })->get();
        
        $usersTable = [];
        foreach ($usersWithUserRole as $user) {
            $usersTable[] = [
                'ID' => $user->id,
                'Nombre' => $user->name,
                'Email' => $user->email
            ];
        }
        $this->table(['ID', 'Nombre', 'Email'], $usersTable);
        
        // Verificar middleware y rutas
        $this->info('Recomendaciones:');
        $this->line('1. Asegúrate de que el middleware CheckPermission esté correctamente configurado.');
        $this->line('2. Verifica que las rutas de productos usen el middleware permission:manage-products|manage-own-products.');
        $this->line('3. En ProductController.php, confirma que se verifica correctamente el permiso manage-own-products.');
        $this->line('4. Revisa que el servicio Angular envíe el header X-User-Permission con el valor correcto.');
        
        return Command::SUCCESS;
    }
} 