<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User; // To assign roles to existing users

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ----------------- DEFINE PERMISSIONS -----------------
        // Permissions are typically structured as {domain}_{action}, e.g., 'order_create'
        $permissions = [
            // User Management
            'user:list', 'user:view', 'user:create', 'user:update', 'user:delete', 'user:assign-roles',
            // Role & Permission Management
            'role:list', 'role:view', 'role:create', 'role:update', 'role:delete', 'permission:list',
            // Customer Management
            'customer:list', 'customer:view', 'customer:create', 'customer:update', 'customer:delete',
            // Order Management
            'order:list', 'order:view', 'order:create', 'order:update', 'order:update-status', 'order:record-payment', 'order:cancel', 'order:delete',
            // Service Management (a single permission for all service components for simplicity, can be broken down further)
            'service-admin:manage',
            // Dashboard
            'dashboard:view',
            // Settings
            'settings:view-profile', 'settings:update-profile', 'settings:change-password', 'settings:manage-application',

            // Product Type Management
            'product-type:list', 'product-type:view', 'product-type:create', 'product-type:update', 'product-type:delete',
            // Service Offering Management
            'service-offering:list', 'service-offering:view', 'service-offering:create', 'service-offering:update', 'service-offering:delete',
            // Service Action Management
            'service-action:list', 'service-action:view', 'service-action:create', 'service-action:update', 'service-action:delete',
            // Service Category Management
            // Reports (Future)
            // 'report:view-financial', 'report:view-operational',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $this->command->info('Permissions created.');


        // ----------------- DEFINE ROLES and ASSIGN PERMISSIONS -----------------

        // ---- Admin Role (Super User) ----
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        // The `before` method in AuthServiceProvider is the best way to give admins all access,
        // but assigning all permissions explicitly also works and is clear.
        $adminRole->givePermissionTo(Permission::all());
        $this->command->info('Admin role created and assigned all permissions.');

        // ---- Receptionist Role ----
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionistRole->syncPermissions([
            'dashboard:view',
            'customer:list', 'customer:view', 'customer:create', 'customer:update',
            'order:list', 'order:view', 'order:create', 'order:update', 'order:update-status', 'order:record-payment', 'order:cancel',
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Receptionist role created and permissions assigned.');

        // ---- Processor Role (Washer/Ironer) ----
        $processorRole = Role::firstOrCreate(['name' => 'processor']);
        $processorRole->syncPermissions([
            'dashboard:view', // Can view a simplified dashboard
            'order:list',     // Can see the list of orders to know what's in the queue
            'order:view',     // Can view order details (e.g., items, notes)
            'order:update-status', // Crucial permission
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Processor role created and permissions assigned.');

        // ---- Delivery Role ----
        $deliveryRole = Role::firstOrCreate(['name' => 'delivery']);
        $deliveryRole->syncPermissions([
            'dashboard:view', // Can view a simplified dashboard
            'order:list',     // To see orders ready for delivery
            'order:view',     // To see delivery address and customer contact
            'order:update-status', // To mark as 'out_for_delivery' or 'delivered'
            'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        ]);
        $this->command->info('Delivery role created and permissions assigned.');


        // ---- Customer Role (Optional, for a customer portal) ----
        // $customerRole = Role::firstOrCreate(['name' => 'customer']);
        // $customerRole->syncPermissions([
        //     'order:view', // A customer can only view their OWN orders (this is enforced in the Policy)
        //     'order:create', // A customer can create a pickup request/order
        //     'settings:view-profile', 'settings:update-profile', 'settings:change-password',
        // ]);
        // $this->command->info('Customer role created and permissions assigned.');


        // ----------------- ASSIGN ROLES TO DEFAULT USERS -----------------
        $adminUser = User::where('email', 'admin@laundry.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
            $this->command->info('Assigned "admin" role to admin@laundry.com');
        }

        $staffUser = User::where('email', 'staff@laundry.com')->first();
        if ($staffUser) {
            // Assign a default role, e.g., 'receptionist'
            $staffUser->assignRole('receptionist');
            $this->command->info('Assigned "receptionist" role to staff@laundry.com');
        }

        $this->command->info('Role and Permission seeding completed.');
    }
}