<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User; // For assigning roles to existing users

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define Permissions
        $permissions = [
            // User Management
            'user_view_any', 'user_view', 'user_create', 'user_update', 'user_delete', 'user_assign_roles',
            // Role Management
            'role_view_any', 'role_view', 'role_create', 'role_update', 'role_delete', 'role_assign_permissions',
            // Permission Management (usually admin only)
            'permission_view_any',
            // Order Management
            'order_view_any', 'order_view', 'order_create', 'order_update', 'order_update_status', 'order_record_payment', 'order_cancel', 'order_delete',
            // Customer Management
            'customer_view_any', 'customer_view', 'customer_create', 'customer_update', 'customer_delete',
            // Service Offerings & Components (ProductCategory, ProductType, ServiceAction, ServiceOffering)
            'service_offering_manage', // A general permission for all service setup CRUD
            'product_category_manage',
            'product_type_manage',
            'service_action_manage',
            // Settings
            'app_settings_manage',
            // Reports
            'report_view_financial', 'report_view_operational',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define Roles and Assign Permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        // Admin gets all permissions (Spatie's Gate `before` method can also handle this)
        $adminRole->givePermissionTo(Permission::all());

        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
        $receptionistRole->givePermissionTo([
            'order_view_any', 'order_view', 'order_create', 'order_update', // May need finer grain for update
            'order_update_status', 'order_record_payment', 'order_cancel',
            'customer_view_any', 'customer_view', 'customer_create', 'customer_update',
        ]);

        $processorRole = Role::firstOrCreate(['name' => 'processor']); // Washer/Ironer
        $processorRole->givePermissionTo([
            'order_view_any', // To see assigned or relevant orders
            'order_view',
            'order_update_status', // Likely restricted to certain status transitions
        ]);

        $deliveryRole = Role::firstOrCreate(['name' => 'delivery']);
        $deliveryRole->givePermissionTo([
            'order_view_any', // To see delivery schedule
            'order_view',
            'order_update_status', // e.g., to mark as 'out_for_delivery', 'delivered'
        ]);

        // Assign roles to existing users (example)
        $adminUser = User::where('email', 'admin@laundry.com')->first();
        if ($adminUser && !$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        $staffUser = User::where('email', 'staff@laundry.com')->first();
        if ($staffUser && !$staffUser->hasRole('receptionist')) { // Assign a default role
            $staffUser->assignRole('receptionist');
        }
    }
}