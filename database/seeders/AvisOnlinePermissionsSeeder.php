<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AvisOnlinePermissionsSeeder extends Seeder
{
    /**
     * Estructura de permisos para AvisOnline
     */
    public function run()
    {
        try {
            $this->info('🚀 Iniciando reestructuración de permisos para AvisOnline...');
            
            // Crear nuevos permisos
            $permissions = $this->createPermissions();
            
            // Crear roles
            $roles = $this->createRoles();
            
            // Asignar permisos a roles
            $this->assignPermissionsToRoles($roles, $permissions);
            
            // Asignar roles a usuarios existentes
            $this->assignRolesToUsers($roles);
            
            $this->info('✅ Reestructuración completada exitosamente!');
            
        } catch (\Exception $e) {
            $this->error('❌ Error en la reestructuración: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }



    /**
     * Crear nuevos permisos específicos para AvisOnline
     */
    private function createPermissions()
    {
        $this->info('🔑 Creando permisos...');
        
        $permissionsData = [
            // Permiso super admin
            'full-admin' => [
                'name' => 'full-admin',
                'description' => 'Administrador total con acceso a todo el sistema'
            ],
            
            // Gestión de usuarios y sistema
            'manage-users' => [
                'name' => 'manage-users',
                'description' => 'Gestionar usuarios, roles y permisos'
            ],
            
            // Gestión de anuncios
            'manage-all-announcements' => [
                'name' => 'manage-all-announcements',
                'description' => 'Gestionar todos los anuncios del sistema'
            ],
            
            'manage-own-announcements' => [
                'name' => 'manage-own-announcements',
                'description' => 'Gestionar solo anuncios propios'
            ],
            
            // Gestión de categorías
            'manage-categories' => [
                'name' => 'manage-categories',
                'description' => 'Gestionar categorías de anuncios'
            ],
            
            // Gestión de sliders
            'manage-sliders' => [
                'name' => 'manage-sliders',
                'description' => 'Gestionar sliders del sitio web'
            ],
        ];
        
        $createdPermissions = [];
        
        foreach ($permissionsData as $key => $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                ['description' => $permData['description']]
            );
            
            $createdPermissions[$key] = $permission;
            $this->info("  ✓ Permiso: {$permission->name}");
        }
        
        return $createdPermissions;
    }

    /**
     * Crear roles específicos para AvisOnline
     */
    private function createRoles()
    {
        $this->info('👥 Creando roles...');
        
        $rolesData = [
            'admin' => [
                'name' => 'Admin',
                'description' => 'Administrador con acceso completo al sistema'
            ],
            'usuario' => [
                'name' => 'Usuario',
                'description' => 'Usuario normal que puede gestionar sus propios anuncios'
            ]
        ];
        
        $createdRoles = [];
        
        foreach ($rolesData as $key => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                ['description' => $roleData['description']]
            );
            
            $createdRoles[$key] = $role;
            $this->info("  ✓ Rol: {$role->name}");
        }
        
        return $createdRoles;
    }

    /**
     * Asignar permisos a roles
     */
    private function assignPermissionsToRoles($roles, $permissions)
    {
        $this->info('🔗 Asignando permisos a roles...');
        
        // Rol Admin: Todos los permisos
        $adminPermissions = collect($permissions)->pluck('id')->toArray();
        $roles['admin']->permissions()->sync($adminPermissions);
        $this->info("  ✓ Admin: " . count($adminPermissions) . " permisos asignados");
        
        // Rol Usuario: Solo anuncios propios
        $userPermissions = [
            $permissions['manage-own-announcements']->id
        ];
        $roles['usuario']->permissions()->sync($userPermissions);
        $this->info("  ✓ Usuario: " . count($userPermissions) . " permisos asignados");
    }

    /**
     * Asignar roles a usuarios existentes
     */
    private function assignRolesToUsers($roles)
    {
        $this->info('👤 Asignando roles a usuarios...');
        
        // Buscar usuarios admin
        $adminEmails = ['admin@sistema.com', 'echodev@gmail.com', 'admin@avisonline.com'];
        $adminUsers = User::whereIn('email', $adminEmails)->get();
        
        foreach ($adminUsers as $user) {
            $user->roles()->sync([$roles['admin']->id]);
            $this->info("  ✓ Rol Admin asignado a: {$user->email}");
        }
        
        // Asignar rol Usuario a todos los demás usuarios
        $regularUsers = User::whereNotIn('email', $adminEmails)->get();
        
        foreach ($regularUsers as $user) {
            $user->roles()->sync([$roles['usuario']->id]);
            $this->info("  ✓ Rol Usuario asignado a: {$user->email}");
        }
        
        $this->info("📊 Total usuarios procesados: " . ($adminUsers->count() + $regularUsers->count()));
    }

    /**
     * Información adicional
     */
    private function info($message)
    {
        echo $message . "\n";
    }

    private function warn($message)
    {
        echo "⚠️ " . $message . "\n";
    }

    private function error($message)
    {
        echo "❌ " . $message . "\n";
    }
} 