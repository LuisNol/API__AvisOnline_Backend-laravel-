<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\Permission;

class RecreateOwnProductsPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:recreate-own-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreate the manage-own-products permission and assign it to Usuario role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recreando permiso manage-own-products...');
        
        // Buscar si ya existe el permiso
        $permission = Permission::where('name', 'manage-own-products')->first();
        
        if ($permission) {
            $this->info('El permiso manage-own-products ya existe.');
        } else {
            // Crear el permiso
            $permission = Permission::create([
                'name' => 'manage-own-products',
                'description' => 'Permite a los usuarios gestionar solo sus propios productos'
            ]);
            $this->info('Permiso manage-own-products creado correctamente.');
        }
        
        // Buscar el rol Usuario
        $userRole = Role::where('name', 'Usuario')->first();
        
        if (!$userRole) {
            $this->error('Rol Usuario no encontrado. Debes crear este rol primero.');
            return Command::FAILURE;
        }
        
        // Asignar el permiso al rol Usuario
        $currentPermissions = $userRole->permissions->pluck('id')->toArray();
        
        // Verificar si el permiso ya está asignado
        if (in_array($permission->id, $currentPermissions)) {
            $this->info('El permiso manage-own-products ya está asignado al rol Usuario.');
        } else {
            // Añadir el nuevo permiso a la lista actual
            $currentPermissions[] = $permission->id;
            $userRole->permissions()->sync($currentPermissions);
            $this->info('Permiso manage-own-products asignado al rol Usuario.');
        }
        
        // Asignar también al rol Admin
        $adminRole = Role::where('name', 'Admin')->first();
        
        if ($adminRole) {
            $adminPermissions = $adminRole->permissions->pluck('id')->toArray();
            
            if (!in_array($permission->id, $adminPermissions)) {
                $adminPermissions[] = $permission->id;
                $adminRole->permissions()->sync($adminPermissions);
                $this->info('Permiso manage-own-products asignado también al rol Admin.');
            }
        }
        
        $this->info('Proceso completado con éxito.');
        return Command::SUCCESS;
    }
} 