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
            // Dashboard
            'dashboard:view',

            // User Management (Admin Only)
            'user:list', 'user:create', 'user:update', 'user:delete', 'user:assign-roles',
            'role:list', 'role:create', 'role:update', 'role:delete', 'permission:list',

            // Customer Management
            'customer:list', 'customer:view', 'customer:create', 'customer:update', 'customer:delete',

            // Order Management
            'order:list', 'order:view', 'order:create', 'order:update', 'order:delete',
            'order:update-status', 'order:record-payment',

            // Expense Management
            'expense:list', 'expense:create', 'expense:update', 'expense:delete',

            // Purchase & Supplier Management
            'supplier:list', 'supplier:create', 'supplier:update', 'supplier:delete',
            'purchase:list', 'purchase:create', 'purchase:update', 'purchase:delete',

            // Service Admin (Categories, Types, Actions, Offerings)
            'service-admin:manage',

            // Settings
            'settings:view-profile', 'settings:update-profile', 'settings:change-password', 'settings:manage-application',

            // Reports
            'report:view-financial', 'report:view-operational',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']); // Specify guard_name for API
        }
        $this->command->info('Permissions created or verified.');

        // ----------------- DEFINE ROLES and ASSIGN PERMISSIONS -----------------

        // ---- Admin Role (Super User) ----
        // Has all permissions implicitly via Gate::before() in AuthServiceProvider.
        // We still create the role itself.
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->command->info('Admin role created.');
        // Note: For clarity, you can still assign all permissions if you prefer not to use Gate::before()
        // $adminRole->givePermissionTo(Permission::all());

        // ---- Receptionist Role ----
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionistRole->syncPermissions([
            'dashboard:view',
            'customer:list', 'customer:view', 'customer:create', 'customer:update',
            'order:list', 'order:view', 'order:create', 'order:update', 'order:update-status', 'order:record-payment',
            'expense:list', 'expense:create',
            'supplier:list', 'supplier:view',
            'purchase:list', 'purchase:view',
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Receptionist role created and permissions assigned.');

        // ---- Processor Role (Washer/Ironer) ----
        $processorRole = Role::firstOrCreate(['name' => 'processor', 'guard_name' => 'web']);
        $processorRole->syncPermissions([
            'order:list',
            'order:view',
            'order:update-status', // Logic in controller should restrict which statuses they can set
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Processor role created and permissions assigned.');
        
        // ---- Delivery Role ----
        $deliveryRole = Role::firstOrCreate(['name' => 'delivery', 'guard_name' => 'web']);
        $deliveryRole->syncPermissions([
            'order:list',
            'order:view',
            'order:update-status', // To mark as delivered
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Delivery role created and permissions assigned.');

        // ----------------- ASSIGN ADMIN ROLE TO ADMIN USER -----------------
        $adminUser = User::where('email', 'admin@laundry.com')->first();
        if ($adminUser) {
            $adminUser->syncRoles(['admin']); // syncRoles is safer than assignRole in a seeder
            $this->command->info('Assigned "admin" role to admin@laundry.com.');
        }
    }
}