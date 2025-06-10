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
    protected $signature = 'avisonline:verify-permissions 
                           {--user= : Verificar permisos de un usuario específico por email}
                           {--role= : Verificar permisos de un rol específico}
                           {--summary : Mostrar solo un resumen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar y analizar todos los permisos, roles y usuarios en AvisOnline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 VERIFICACIÓN COMPLETA DE PERMISOS AVISONLINE');
        $this->info('================================================');

        // Si se especifica un usuario
        if ($this->option('user')) {
            return $this->verifySpecificUser($this->option('user'));
        }

        // Si se especifica un rol
        if ($this->option('role')) {
            return $this->verifySpecificRole($this->option('role'));
        }

        // Verificación completa
        $this->verifySystemStructure();
        
        if (!$this->option('summary')) {
            $this->verifyPermissions();
            $this->verifyRoles();
            $this->verifyUsers();
        }
        
        $this->verifyConsistency();
        $this->showRecommendations();

        return Command::SUCCESS;
    }

    /**
     * Verificar estructura general del sistema
     */
    private function verifySystemStructure()
    {
        $this->info("\n📊 ESTRUCTURA DEL SISTEMA");
        $this->info("=========================");

        $permissions = Permission::count();
        $roles = Role::count();
        $users = User::count();
        $usersWithRoles = User::has('roles')->count();

        $this->table([
            'Elemento', 'Cantidad', 'Estado'
        ], [
            ['Permisos', $permissions, $permissions >= 6 ? '✅ OK' : '⚠️ Pocos'],
            ['Roles', $roles, $roles >= 2 ? '✅ OK' : '⚠️ Faltan'],
            ['Usuarios', $users, $users > 0 ? '✅ OK' : '❌ Sin usuarios'],
            ['Usuarios con roles', $usersWithRoles, $usersWithRoles > 0 ? '✅ OK' : '❌ Sin asignaciones'],
        ]);
    }

    /**
     * Verificar permisos específicos
     */
    private function verifyPermissions()
    {
        $this->info("\n🔑 PERMISOS AVISONLINE");
        $this->info("=====================");

        $expectedPermissions = [
            'full-admin' => 'Super administrador con acceso total',
            'manage-users' => 'Gestionar usuarios, roles y permisos',
            'manage-all-announcements' => 'Gestionar todos los anuncios',
            'manage-own-announcements' => 'Gestionar solo anuncios propios',
            'manage-categories' => 'Gestionar categorías de anuncios',
            'manage-sliders' => 'Gestionar sliders del sitio web'
        ];

        $permissions = Permission::all()->keyBy('name');
        $permissionData = [];

        foreach ($expectedPermissions as $name => $description) {
            $exists = $permissions->has($name);
            $permissionData[] = [
                $name,
                $exists ? '✅ Existe' : '❌ Falta',
                $exists ? $permissions[$name]->description ?? $description : $description
            ];
        }

        $this->table(['Permiso', 'Estado', 'Descripción'], $permissionData);

        // Verificar permisos obsoletos
        $obsoletePermissions = Permission::whereIn('name', ['manage-products', 'manage-own-products'])->get();
        if ($obsoletePermissions->count() > 0) {
            $this->warn("\n⚠️ PERMISOS OBSOLETOS ENCONTRADOS:");
            foreach ($obsoletePermissions as $perm) {
                $this->line("  - {$perm->name} (ID: {$perm->id})");
            }
            $this->line("  💡 Considera eliminarlos después de verificar que no se usan.");
        }
    }

    /**
     * Verificar roles y sus permisos
     */
    private function verifyRoles()
    {
        $this->info("\n👥 ROLES Y PERMISOS");
        $this->info("==================");

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $this->info("\n🏷️ ROL: {$role->name}");
            
            if ($role->permissions->isEmpty()) {
                $this->error("  ❌ No tiene permisos asignados");
            } else {
                $this->info("  📋 Permisos asignados:");
                foreach ($role->permissions as $permission) {
                    $this->line("    ✓ {$permission->name}");
                }
            }

            // Verificaciones específicas por rol
            if ($role->name === 'Admin') {
                $hasFullAdmin = $role->permissions->contains('name', 'full-admin');
                $hasManageUsers = $role->permissions->contains('name', 'manage-users');
                
                if (!$hasFullAdmin && !$hasManageUsers) {
                    $this->warn("  ⚠️ Admin debería tener 'full-admin' o 'manage-users'");
                }
            }

            if ($role->name === 'Usuario') {
                $hasOwnAnnouncements = $role->permissions->contains('name', 'manage-own-announcements');
                
                if (!$hasOwnAnnouncements) {
                    $this->error("  ❌ Usuario debería tener 'manage-own-announcements'");
                }
            }
        }
    }

    /**
     * Verificar usuarios y sus roles
     */
    private function verifyUsers()
    {
        $this->info("\n👤 USUARIOS DEL SISTEMA");
        $this->info("======================");

        // Usuarios Admin
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        $this->info("\n🔥 ADMINISTRADORES:");
        if ($adminUsers->isEmpty()) {
            $this->error("  ❌ NO HAY USUARIOS ADMINISTRADORES");
            $this->warn("  🚨 CRÍTICO: Crea al menos un administrador");
        } else {
            foreach ($adminUsers as $user) {
                $this->line("  ✓ {$user->name} ({$user->email})");
            }
        }

        // Usuarios normales
        $regularUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Usuario');
        })->get();

        $this->info("\n👥 USUARIOS NORMALES:");
        if ($regularUsers->isEmpty()) {
            $this->warn("  ⚠️ No hay usuarios normales");
        } else {
            $this->line("  📊 Total: {$regularUsers->count()} usuarios");
            if ($regularUsers->count() <= 5) {
                foreach ($regularUsers as $user) {
                    $this->line("  • {$user->name} ({$user->email})");
                }
            }
        }

        // Usuarios sin roles
        $usersWithoutRoles = User::doesntHave('roles')->get();
        if ($usersWithoutRoles->count() > 0) {
            $this->warn("\n⚠️ USUARIOS SIN ROLES ({$usersWithoutRoles->count()}):");
            foreach ($usersWithoutRoles->take(3) as $user) {
                $this->line("  • {$user->name} ({$user->email})");
            }
            if ($usersWithoutRoles->count() > 3) {
                $this->line("  ... y " . ($usersWithoutRoles->count() - 3) . " más");
            }
        }
    }

    /**
     * Verificar usuario específico
     */
    private function verifySpecificUser($email)
    {
        $user = User::where('email', $email)->with('roles.permissions')->first();

        if (!$user) {
            $this->error("❌ Usuario con email '{$email}' no encontrado");
            return Command::FAILURE;
        }

        $this->info("👤 USUARIO: {$user->name} ({$user->email})");
        $this->info("==========================================");

        if ($user->roles->isEmpty()) {
            $this->error("❌ Este usuario no tiene roles asignados");
            return Command::FAILURE;
        }

        foreach ($user->roles as $role) {
            $this->info("\n🏷️ ROL: {$role->name}");
            $this->info("📋 PERMISOS:");
            
            if ($role->permissions->isEmpty()) {
                $this->warn("  ⚠️ Este rol no tiene permisos");
            } else {
                foreach ($role->permissions as $permission) {
                    $this->line("  ✓ {$permission->name}");
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Verificar rol específico
     */
    private function verifySpecificRole($roleName)
    {
        $role = Role::where('name', $roleName)->with('permissions', 'users')->first();

        if (!$role) {
            $this->error("❌ Rol '{$roleName}' no encontrado");
            return Command::FAILURE;
        }

        $this->info("🏷️ ROL: {$role->name}");
        $this->info("===================");

        $this->info("\n📋 PERMISOS ASIGNADOS:");
        if ($role->permissions->isEmpty()) {
            $this->warn("  ⚠️ Este rol no tiene permisos asignados");
        } else {
            foreach ($role->permissions as $permission) {
                $this->line("  ✓ {$permission->name}");
            }
        }

        $this->info("\n👥 USUARIOS CON ESTE ROL:");
        if ($role->users->isEmpty()) {
            $this->warn("  ⚠️ No hay usuarios con este rol");
        } else {
            foreach ($role->users as $user) {
                $this->line("  • {$user->name} ({$user->email})");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Verificar consistencia del sistema
     */
    private function verifyConsistency()
    {
        $this->info("\n🔍 VERIFICACIÓN DE CONSISTENCIA");
        $this->info("===============================");

        $issues = [];

        // Verificar que hay al menos un admin
        $adminCount = User::whereHas('roles', function ($query) {
            $query->where('name', 'Admin');
        })->count();

        if ($adminCount === 0) {
            $issues[] = "❌ CRÍTICO: No hay usuarios administradores";
        } else {
            $this->line("✅ Hay {$adminCount} administrador(es)");
        }

        // Verificar permisos críticos
        $criticalPermissions = ['full-admin', 'manage-users', 'manage-own-announcements'];
        $missingPermissions = [];

        foreach ($criticalPermissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                $missingPermissions[] = $permission;
            }
        }

        if (!empty($missingPermissions)) {
            $issues[] = "❌ Faltan permisos críticos: " . implode(', ', $missingPermissions);
        } else {
            $this->line("✅ Todos los permisos críticos existen");
        }

        // Verificar que Usuario rol tiene permiso básico
        $usuarioRole = Role::where('name', 'Usuario')->with('permissions')->first();
        if ($usuarioRole && !$usuarioRole->permissions->contains('name', 'manage-own-announcements')) {
            $issues[] = "❌ Rol 'Usuario' no tiene permiso 'manage-own-announcements'";
        } else {
            $this->line("✅ Rol Usuario tiene permisos básicos");
        }

        if (empty($issues)) {
            $this->info("\n🎉 ¡SISTEMA CONSISTENTE! No se encontraron problemas críticos.");
        } else {
            $this->warn("\n⚠️ PROBLEMAS ENCONTRADOS:");
            foreach ($issues as $issue) {
                $this->line("  {$issue}");
            }
        }
    }

    /**
     * Mostrar recomendaciones
     */
    private function showRecommendations()
    {
        $this->info("\n💡 RECOMENDACIONES");
        $this->info("==================");

        $this->line("1. 🔄 Para reestructurar permisos: php artisan avisonline:restructure-permissions");
        $this->line("2. 👤 Para verificar un usuario: php artisan avisonline:verify-permissions --user=email@example.com");
        $this->line("3. 🏷️ Para verificar un rol: php artisan avisonline:verify-permissions --role=Admin");
        $this->line("4. 📊 Para resumen rápido: php artisan avisonline:verify-permissions --summary");
        $this->line("5. 🧹 Limpia regularmente usuarios sin roles y permisos obsoletos");
    }
} 