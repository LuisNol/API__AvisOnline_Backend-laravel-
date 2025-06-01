<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

class VerifyPermissions extends Command
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
    protected $description = 'Verify and list all permissions and roles in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando permisos y roles en el sistema...');

        // Listar todos los permisos
        $permissions = Permission::all();
        $this->info('');
        $this->info('=== PERMISOS EN EL SISTEMA ===');
        
        if ($permissions->isEmpty()) {
            $this->error('No hay permisos registrados en el sistema.');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Slug', 'Descripción', 'Creado'],
                $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'slug' => $permission->slug ?? 'N/A',
                        'description' => $permission->description ?? 'N/A',
                        'created_at' => $permission->created_at->format('Y-m-d H:i')
                    ];
                })
            );
        }

        // Listar todos los roles
        $roles = Role::with('permissions')->get();
        $this->info('');
        $this->info('=== ROLES EN EL SISTEMA ===');
        
        if ($roles->isEmpty()) {
            $this->error('No hay roles registrados en el sistema.');
        } else {
            foreach ($roles as $role) {
                $this->info("Role: {$role->name} (ID: {$role->id})");
                
                if ($role->permissions->isEmpty()) {
                    $this->warn("  - No tiene permisos asignados");
                } else {
                    $this->info("  - Permisos asignados:");
                    foreach ($role->permissions as $permission) {
                        $this->line("    * {$permission->name}");
                    }
                }
                $this->info('');
            }
        }

        // Verificar usuarios y sus roles
        $this->info('=== USUARIOS CON ROL ADMIN ===');
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        if ($adminUsers->isEmpty()) {
            $this->error('No hay usuarios con rol Admin.');
        } else {
            $this->table(
                ['ID', 'Nombre', 'Email', 'Roles'],
                $adminUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name')->implode(', ')
                    ];
                })
            );
        }

        // Verificar consistencia de permisos
        $this->info('');
        $this->info('=== VERIFICACIÓN DE CONSISTENCIA DE PERMISOS ===');
        
        // Verificar permisos duplicados o similares
        $permissionNames = $permissions->pluck('name')->toArray();
        $similar = [];
        
        foreach ($permissionNames as $i => $name1) {
            foreach (array_slice($permissionNames, $i + 1) as $name2) {
                $similarity = similar_text($name1, $name2, $percent);
                if ($percent > 70) {
                    $similar[] = [$name1, $name2, round($percent, 2) . '%'];
                }
            }
        }
        
        if (empty($similar)) {
            $this->info('No se encontraron permisos similares que puedan causar confusión.');
        } else {
            $this->warn('Se encontraron permisos similares que podrían causar confusión:');
            $this->table(['Permiso 1', 'Permiso 2', 'Similitud'], $similar);
        }

        return Command::SUCCESS;
    }
} 