<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class FixPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permissions issues in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando reparación de permisos del sistema...');
        
        // Verificar roles existentes
        $this->info('Verificando rol Admin...');
        $adminRole = Role::where('name', 'Admin')->first();
        if (!$adminRole) {
            $this->warn('Rol Admin no encontrado. Creando...');
            $adminRole = Role::create([
                'name' => 'Admin',
                'description' => 'Administrador con acceso completo'
            ]);
            $this->info('Rol Admin creado correctamente.');
        } else {
            $this->info('Rol Admin encontrado.');
        }
        
        $this->info('Verificando rol Usuario...');
        $userRole = Role::where('name', 'Usuario')->first();
        if (!$userRole) {
            $this->warn('Rol Usuario no encontrado. Creando...');
            $userRole = Role::create([
                'name' => 'Usuario',
                'description' => 'Usuario regular con acceso limitado'
            ]);
            $this->info('Rol Usuario creado correctamente.');
        } else {
            $this->info('Rol Usuario encontrado.');
        }
        
        // Definir permisos requeridos
        $requiredPermissions = [
            'manage-users' => 'Gestionar usuarios del sistema',
            'manage-products' => 'Gestionar todos los productos del sistema',
            'manage-own-products' => 'Gestionar solo productos propios'
        ];
        
        // Verificar y crear permisos
        $this->info('Verificando permisos requeridos...');
        foreach ($requiredPermissions as $name => $description) {
            $permission = Permission::where('name', $name)->first();
            if (!$permission) {
                $this->warn("Permiso '{$name}' no encontrado. Creando...");
                $permission = Permission::create([
                    'name' => $name,
                    'description' => $description
                ]);
                $this->info("Permiso '{$name}' creado correctamente.");
            } else {
                $this->info("Permiso '{$name}' encontrado.");
            }
        }
        
        // Asignar permisos a roles
        $this->info('Asignando permisos a roles...');
        
        // Asignar todos los permisos al rol Admin
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id')->toArray());
        $this->info('Todos los permisos asignados al rol Admin.');
        
        // Asignar permisos específicos al rol Usuario
        $userPermissions = Permission::where('name', 'manage-own-products')->get();
        $userRole->permissions()->sync($userPermissions->pluck('id')->toArray());
        $this->info('Permisos de gestión de productos propios asignados al rol Usuario.');
        
        // Verificar usuarios Admin
        $this->info('Verificando usuarios Admin...');
        $adminCount = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->count();
        
        if ($adminCount == 0) {
            $this->warn('No hay usuarios con rol Admin. Por favor, asigna el rol Admin a al menos un usuario.');
        } else {
            $this->info("Hay {$adminCount} usuario(s) con rol Admin.");
        }
        
        // Buscar permisos duplicados o mal formados
        $this->info('Buscando permisos duplicados o mal formados...');
        $permissionNames = Permission::pluck('name')->toArray();
        $similar = [];
        
        foreach ($permissionNames as $i => $name1) {
            foreach (array_slice($permissionNames, $i + 1) as $name2) {
                $similarity = similar_text($name1, $name2, $percent);
                if ($percent > 80 && $name1 != $name2) {
                    $similar[] = [$name1, $name2, round($percent, 2) . '%'];
                    
                    // Preguntar si se deben fusionar
                    if ($this->confirm("Los permisos '{$name1}' y '{$name2}' son muy similares. ¿Deseas fusionarlos y mantener '{$name1}'?")) {
                        // Obtener el permiso a mantener y el que se eliminará
                        $keepPermission = Permission::where('name', $name1)->first();
                        $deletePermission = Permission::where('name', $name2)->first();
                        
                        if ($keepPermission && $deletePermission) {
                            // Transferir relaciones de roles
                            DB::table('permission_role')
                                ->where('permission_id', $deletePermission->id)
                                ->update(['permission_id' => $keepPermission->id]);
                            
                            // Eliminar el permiso duplicado
                            $deletePermission->delete();
                            $this->info("Se fusionaron los permisos. '{$name2}' fue eliminado y sus relaciones transferidas a '{$name1}'.");
                        }
                    }
                }
            }
        }
        
        if (empty($similar)) {
            $this->info('No se encontraron permisos similares que puedan causar confusión.');
        }
        
        $this->info('La reparación de permisos ha finalizado.');
        return Command::SUCCESS;
    }
} 