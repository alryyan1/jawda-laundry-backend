<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ----------------- DEFINE PERMISSIONS -----------------
        $permissions = [
            // General
            'dashboard:view',
            'admin:view-menu', // To see the main "Administration" menu

            // Navigation Management
            'navigation:view', 'navigation:create', 'navigation:update', 'navigation:delete',
            'user-navigation:manage', 'user-navigation:view',

            // User & Role Management
            'user:list', 'user:create', 'user:update', 'user:delete', 'user:assign-roles',
            'role:list', 'role:create', 'role:update', 'role:delete', 'permission:list',

            // Customer Management
            'customer:list', 'customer:view', 'customer:create', 'customer:update', 'customer:delete',

            // Order Management
            'order:list', 'order:view', 'order:create', 'order:update', 'order:delete',
            'order:update-status', 'order:record-payment',

            // Expense Management
            'expense:list', 'expense:create', 'expense:update', 'expense:delete',
            'expense-category:manage', // Single permission for simple category CRUD

            // Purchase & Supplier Management
            'supplier:list', 'supplier:view', 'supplier:create', 'supplier:update', 'supplier:delete',
            'purchase:list', 'purchase:view', 'purchase:create', 'purchase:update', 'purchase:delete',

            // Service Admin
            'service-admin:manage', // General permission for all service setup CRUD

            // Settings
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
            'settings:manage-application', // For app-wide settings

            // Reports
            'report:view-financial', 'report:view-operational',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->command->info('Permissions created or verified.');

        // ----------------- DEFINE ROLES and ASSIGN PERMISSIONS -----------------

        // ---- Admin Role (Super User) ----
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all()); // Admin gets all permissions
        $this->command->info('Admin role created and assigned all permissions.');

        // ---- Receptionist Role ----
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionistRole->syncPermissions([
            'dashboard:view',
            'customer:list', 'customer:view', 'customer:create', 'customer:update',
            'order:list', 'order:view', 'order:create', 'order:update', 'order:update-status', 'order:record-payment',
            'expense:list', 'expense:create',
            'supplier:list', 'supplier:view',
            'purchase:list', 'purchase:view', 'purchase:create',
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Receptionist role created and permissions assigned.');

        // ---- Processor Role (Washer/Ironer) ----
        $processorRole = Role::firstOrCreate(['name' => 'processor', 'guard_name' => 'web']);
        $processorRole->syncPermissions([
            'order:list',
            'order:view',
            'order:update-status',
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Processor role created and permissions assigned.');
        
        // ---- Delivery Role ----
        $deliveryRole = Role::firstOrCreate(['name' => 'delivery', 'guard_name' => 'web']);
        $deliveryRole->syncPermissions([
            'order:list',
            'order:view',
            'order:update-status',
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Delivery role created and permissions assigned.');

        // ----------------- ASSIGN ROLES TO DEFAULT USERS -----------------
        $adminUser = User::where('email', 'admin@admin.com')->first();
        if ($adminUser) {
            $adminUser->syncRoles(['admin']);
            $this->command->info('Assigned "admin" role to admin@admin.com.');
        }

        $staffUser = User::where('email', 'staff@staff.com')->first();
        if ($staffUser) {
            $staffUser->syncRoles(['receptionist']);
            $this->command->info('Assigned "receptionist" role to staff@staff.com.');
        }
    }
}