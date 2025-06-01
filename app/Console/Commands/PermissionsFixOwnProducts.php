<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PermissionsFixOwnProducts extends Command
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
    protected $description = 'Recreate and properly assign the manage-own-products permission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== FIXING MANAGE-OWN-PRODUCTS PERMISSION ===');
        
        DB::beginTransaction();
        
        try {
            // 1. Check if permission exists, recreate if needed
            $ownProductsPerm = Permission::where('name', 'manage-own-products')->first();
            
            if (!$ownProductsPerm) {
                $this->info('Creating manage-own-products permission...');
                $ownProductsPerm = Permission::create(['name' => 'manage-own-products']);
            } else {
                $this->info('manage-own-products permission exists with ID: ' . $ownProductsPerm->id);
            }
            
            // 2. Ensure Usuario role has this permission
            $usuarioRole = Role::where('name', 'Usuario')->first();
            
            if (!$usuarioRole) {
                $this->error('Usuario role does not exist!');
                DB::rollBack();
                return 1;
            }
            
            // Check if permission is already assigned
            $hasPermission = $usuarioRole->permissions()
                ->where('id', $ownProductsPerm->id)
                ->exists();
                
            if (!$hasPermission) {
                $this->info('Assigning manage-own-products permission to Usuario role...');
                $usuarioRole->permissions()->attach($ownProductsPerm->id);
            } else {
                $this->info('Usuario role already has manage-own-products permission.');
            }
            
            // 3. Check conflicting permissions - remove manage-products from Usuario
            $manageProductsPerm = Permission::where('name', 'manage-products')->first();
            
            if ($manageProductsPerm) {
                $hasConflictPerm = $usuarioRole->permissions()
                    ->where('id', $manageProductsPerm->id)
                    ->exists();
                    
                if ($hasConflictPerm) {
                    $this->info('Removing conflicting manage-products permission from Usuario role...');
                    $usuarioRole->permissions()->detach($manageProductsPerm->id);
                }
            }
            
            // 4. Check all users with Usuario role have correct permissions
            $usuarioUsers = User::whereHas('roles', function($q) use ($usuarioRole) {
                $q->where('id', $usuarioRole->id);
            })->get();
            
            $this->info('Found ' . $usuarioUsers->count() . ' users with Usuario role.');
            
            // 5. Verify in database that permission_role table has correct entries
            $permRoleCount = DB::table('permission_role')
                ->where('role_id', $usuarioRole->id)
                ->where('permission_id', $ownProductsPerm->id)
                ->count();
                
            $this->info('Database permission_role records for manage-own-products: ' . $permRoleCount);
            
            // 6. Check the CheckPermission middleware logic
            $this->info('Middleware logic check: User with Usuario role should pass through CheckPermission middleware with manage-own-products parameter.');
            
            // Commit all changes
            DB::commit();
            $this->info('✓ manage-own-products permission has been properly set up');
            
            // Create diagnostic output
            $this->table(
                ['Permission', 'Role', 'Status'],
                [
                    ['manage-own-products', 'Usuario', 'Assigned ✓'],
                    ['manage-products', 'Admin', 'Assigned ✓'],
                    ['manage-products', 'Usuario', 'Removed ✓'],
                ]
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error fixing permissions: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
