<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PermissionsVerify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:verify {--fix : Fix permission issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify and diagnose permissions issues in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PERMISSION DIAGNOSTICS ===');
        
        // Check if required permissions exist
        $this->checkRequiredPermissions();
        
        // Check role permissions assignments
        $this->checkRolePermissions();
        
        // Check user role assignments
        $this->checkUserRoles();
        
        // Check for duplicate permissions
        $this->checkDuplicatePermissions();
        
        // Fix issues if requested
        if ($this->option('fix')) {
            $this->info('Fixing permission issues...');
            $this->fixPermissionIssues();
        } else {
            $this->info('Run with --fix option to automatically fix identified issues.');
        }
    }
    
    protected function checkRequiredPermissions()
    {
        $this->info('Checking required permissions...');
        
        $requiredPermissions = [
            'manage-users',
            'manage-products',
            'manage-own-products'
        ];
        
        $missingPermissions = [];
        
        foreach ($requiredPermissions as $permName) {
            $exists = Permission::where('name', $permName)->exists();
            $this->line(" - {$permName}: " . ($exists ? '✓' : '✗'));
            
            if (!$exists) {
                $missingPermissions[] = $permName;
            }
        }
        
        if (!empty($missingPermissions)) {
            $this->warn('Missing permissions: ' . implode(', ', $missingPermissions));
        } else {
            $this->info('All required permissions exist.');
        }
    }
    
    protected function checkRolePermissions()
    {
        $this->info('Checking role-permission assignments...');
        
        // Get all roles
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $this->line("Role: {$role->name}");
            $permissions = $role->permissions()->pluck('name')->toArray();
            
            if (empty($permissions)) {
                $this->warn(" - No permissions assigned");
            } else {
                $this->line(" - Permissions: " . implode(', ', $permissions));
            }
            
            // Check for specific issues
            if ($role->name === 'Admin') {
                // Admin should have manage-products
                if (!in_array('manage-products', $permissions)) {
                    $this->error(" - Admin is missing manage-products permission");
                }
                
                // Admin should not need manage-own-products
                if (in_array('manage-own-products', $permissions)) {
                    $this->warn(" - Admin has manage-own-products permission which is redundant");
                }
            }
            
            if ($role->name === 'Usuario') {
                // Regular users should have manage-own-products
                if (!in_array('manage-own-products', $permissions)) {
                    $this->error(" - Usuario role is missing manage-own-products permission");
                }
                
                // Regular users should not have manage-products
                if (in_array('manage-products', $permissions)) {
                    $this->error(" - Usuario role has manage-products which could cause conflicts");
                }
            }
        }
    }
    
    protected function checkUserRoles()
    {
        $this->info('Checking user-role assignments...');
        
        // Get all users
        $users = User::has('roles')->get();
        $usersWithoutRoles = User::doesntHave('roles')->count();
        
        if ($usersWithoutRoles > 0) {
            $this->warn("Found {$usersWithoutRoles} users without any roles assigned");
        }
        
        $this->line("Users with roles: {$users->count()}");
        
        // Check for specific issues
        $usersWith2Roles = 0;
        foreach ($users as $user) {
            $roles = $user->roles()->pluck('name')->toArray();
            if (count($roles) > 1) {
                $usersWith2Roles++;
                $this->line(" - User {$user->id} ({$user->email}) has multiple roles: " . implode(', ', $roles));
                
                // Check for Admin + Usuario conflict
                if (in_array('Admin', $roles) && in_array('Usuario', $roles)) {
                    $this->warn("   ⚠ User has both Admin and Usuario roles, may cause permission conflicts");
                }
            }
        }
        
        if ($usersWith2Roles > 0) {
            $this->line("Found {$usersWith2Roles} users with multiple roles");
        }
    }
    
    protected function checkDuplicatePermissions()
    {
        $this->info('Checking for duplicate permission records...');
        
        $permNames = Permission::pluck('name')->toArray();
        $duplicates = array_count_values($permNames);
        
        $hasDuplicates = false;
        foreach ($duplicates as $name => $count) {
            if ($count > 1) {
                $hasDuplicates = true;
                $this->error(" - Permission '{$name}' has {$count} duplicate records");
            }
        }
        
        if (!$hasDuplicates) {
            $this->info('No duplicate permission records found.');
        }
    }
    
    protected function fixPermissionIssues()
    {
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // 1. Create missing permissions
            $requiredPermissions = ['manage-users', 'manage-products', 'manage-own-products'];
            foreach ($requiredPermissions as $permName) {
                if (!Permission::where('name', $permName)->exists()) {
                    Permission::create(['name' => $permName]);
                    $this->info("Created missing permission: {$permName}");
                }
            }
            
            // 2. Fix role-permission assignments
            
            // Admin role should have manage-products but not manage-own-products
            $adminRole = Role::where('name', 'Admin')->first();
            if ($adminRole) {
                $manageProductsPerm = Permission::where('name', 'manage-products')->first();
                $manageOwnProductsPerm = Permission::where('name', 'manage-own-products')->first();
                
                if ($manageProductsPerm && !$adminRole->permissions()->where('id', $manageProductsPerm->id)->exists()) {
                    $adminRole->permissions()->attach($manageProductsPerm->id);
                    $this->info("Added manage-products permission to Admin role");
                }
                
                // Remove redundant permission
                if ($manageOwnProductsPerm && $adminRole->permissions()->where('id', $manageOwnProductsPerm->id)->exists()) {
                    $adminRole->permissions()->detach($manageOwnProductsPerm->id);
                    $this->info("Removed redundant manage-own-products permission from Admin role");
                }
            }
            
            // Usuario role should have manage-own-products but not manage-products
            $userRole = Role::where('name', 'Usuario')->first();
            if ($userRole) {
                $manageProductsPerm = Permission::where('name', 'manage-products')->first();
                $manageOwnProductsPerm = Permission::where('name', 'manage-own-products')->first();
                
                if ($manageOwnProductsPerm && !$userRole->permissions()->where('id', $manageOwnProductsPerm->id)->exists()) {
                    $userRole->permissions()->attach($manageOwnProductsPerm->id);
                    $this->info("Added manage-own-products permission to Usuario role");
                }
                
                // Remove conflicting permission
                if ($manageProductsPerm && $userRole->permissions()->where('id', $manageProductsPerm->id)->exists()) {
                    $userRole->permissions()->detach($manageProductsPerm->id);
                    $this->info("Removed conflicting manage-products permission from Usuario role");
                }
            }
            
            // 3. Fix users with conflicting roles
            $users = User::whereHas('roles', function($q) {
                $q->where('name', 'Admin');
            })->whereHas('roles', function($q) {
                $q->where('name', 'Usuario');
            })->get();
            
            foreach ($users as $user) {
                // Keep Admin role, remove Usuario role
                $userRole = Role::where('name', 'Usuario')->first();
                if ($userRole) {
                    $user->roles()->detach($userRole->id);
                    $this->info("Removed conflicting Usuario role from Admin user {$user->id}");
                }
            }
            
            // Commit the changes
            DB::commit();
            $this->info('Permission issues fixed successfully.');
            
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
            $this->error('Error fixing permissions: ' . $e->getMessage());
        }
    }
}
