<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NavigationItem;
use Spatie\Permission\Models\Role;

class UserNavigationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Setting up navigation permissions for different roles...');

        // Get all navigation items
        $allNavigationItems = NavigationItem::all();
        
        // Get roles
        $adminRole = Role::where('name', 'admin')->first();
        $staffRole = Role::where('name', 'staff')->first();
        $receptionistRole = Role::where('name', 'receptionist')->first();
        $processorRole = Role::where('name', 'processor')->first();
        $deliveryRole = Role::where('name', 'delivery')->first();

        // Define which navigation items each role should have access to
        $roleNavigationAccess = [
            'admin' => [
                // Admin gets access to all navigation items
                'dashboard', 'pos', 'orders', 'customers', 'services', 'dining', 
                'expenses', 'purchases', 'suppliers', 'reports', 'admin',
                // All sub-items
                'orders_list',
                'customers_list', 'customers_new',
                'services_offerings', 'services_menu', 'services_categories', 'services_types', 'services_actions',
                'expenses_list', 'expenses_categories',
                'purchases_list', 'purchases_new',
                'reports_sales', 'reports_costs', 'reports_orders', 'reports_daily_revenue', 'reports_daily_costs', 'reports_detailed',
                'admin_users', 'admin_roles', 'admin_navigation', 'admin_restaurant_tables', 'admin_settings'
            ],
            'staff' => [
                // Staff gets access to POS, Orders, and Expenses only
                'pos', 'orders', 'expenses',
                // Sub-items for these main items
                'orders_list',
                'expenses_list'
            ],
            'receptionist' => [
                // Receptionist gets access to POS, Orders, and Expenses only
                'pos', 'orders', 'expenses',
                // Sub-items for these main items
                'orders_list',
                'expenses_list'
            ],
            'processor' => [
                // Processor gets access to Orders only
                'orders',
                'orders_list'
            ],
            'delivery' => [
                // Delivery gets access to Orders only
                'orders',
                'orders_list'
            ]
        ];

        // Process each role
        foreach ($roleNavigationAccess as $roleName => $allowedNavigationKeys) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                $this->command->warn("Role '{$roleName}' not found, skipping...");
                continue;
            }

            $this->command->info("Setting up navigation permissions for role: {$roleName}");

            // Get all users with this role
            $usersWithRole = User::role($roleName)->get();

            foreach ($usersWithRole as $user) {
                $this->setupUserNavigationPermissions($user, $allNavigationItems, $allowedNavigationKeys);
            }
        }

        // For new users created in the future, we need to set up a default role
        // This will be handled in the User model's boot method or in the user creation process
        $this->command->info('Navigation permissions setup completed!');
    }

    /**
     * Set up navigation permissions for a specific user
     */
    private function setupUserNavigationPermissions(User $user, $allNavigationItems, array $allowedNavigationKeys): void
    {
        $user->navigationItems()->detach(); // Remove existing permissions

        foreach ($allNavigationItems as $navigationItem) {
            $isGranted = in_array($navigationItem->key, $allowedNavigationKeys);
            
            $user->navigationItems()->attach($navigationItem->id, [
                'is_granted' => $isGranted
            ]);
        }

        $this->command->info("Set up navigation permissions for user: {$user->name} ({$user->email})");
    }

    /**
     * Set up default navigation permissions for a new user based on their role
     */
    public static function setupDefaultNavigationForUser(User $user): void
    {
        $role = $user->roles->first();
        if (!$role) {
            return; // No role assigned, no navigation permissions
        }

        $allNavigationItems = NavigationItem::all();
        
        // Define default navigation access based on role
        $defaultNavigationAccess = [
            'admin' => [
                'dashboard', 'pos', 'orders', 'customers', 'services', 'dining', 
                'expenses', 'purchases', 'suppliers', 'reports', 'admin',
                'orders_list',
                'customers_list', 'customers_new',
                'services_offerings', 'services_menu', 'services_categories', 'services_types', 'services_actions',
                'expenses_list', 'expenses_categories',
                'purchases_list', 'purchases_new',
                'reports_sales', 'reports_costs', 'reports_orders', 'reports_daily_revenue', 'reports_daily_costs', 'reports_detailed',
                'admin_users', 'admin_roles', 'admin_navigation', 'admin_restaurant_tables', 'admin_settings'
            ],
            'staff' => [
                'pos', 'orders', 'expenses',
                'orders_list',
                'expenses_list'
            ],
            'receptionist' => [
                'pos', 'orders', 'expenses',
                'orders_list',
                'expenses_list'
            ],
            'processor' => [
                'orders',
                'orders_list'
            ],
            'delivery' => [
                'orders',
                'orders_list'
            ]
        ];

        $allowedNavigationKeys = $defaultNavigationAccess[$role->name] ?? [];

        foreach ($allNavigationItems as $navigationItem) {
            $isGranted = in_array($navigationItem->key, $allowedNavigationKeys);
            
            $user->navigationItems()->attach($navigationItem->id, [
                'is_granted' => $isGranted
            ]);
        }
    }
}
