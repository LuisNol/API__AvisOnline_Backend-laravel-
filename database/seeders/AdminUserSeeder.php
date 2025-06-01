<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin'],
            ['description' => 'Administrator with full privileges']
        );

        // Create basic permissions
        $manageUsersPermission = Permission::firstOrCreate(
            ['name' => 'manage-users'],
            ['description' => 'Can manage all users']
        );
        
        $manageProductsPermission = Permission::firstOrCreate(
            ['name' => 'manage-products'],
            ['description' => 'Can manage all products']
        );

        // Assign permissions to admin role
        $adminRole->permissions()->syncWithoutDetaching([$manageUsersPermission->id, $manageProductsPermission->id]);

        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'type_user' => 1,
                'password' => Hash::make('password123'),
                'email_verified_at' => now()
            ]
        );

        // Assign admin role to user
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        
        $this->command->info('Admin user created: admin@example.com / password123');
    }
} 