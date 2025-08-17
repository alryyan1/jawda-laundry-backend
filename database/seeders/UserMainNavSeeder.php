<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserMainNav;

class UserMainNavSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the admin user
        $adminUser = User::where('email', 'admin@admin.com')->first();
        
        if ($adminUser) {
            // Create some example custom navigation items for the admin user
            UserMainNav::create([
                'user_id' => $adminUser->id,
                'key' => 'custom_dashboard',
                'title' => [
                    'en' => 'Custom Dashboard',
                    'ar' => 'لوحة تحكم مخصصة'
                ],
                'icon' => 'LayoutDashboard',
                'route' => '/custom-dashboard',
                'sort_order' => 1,
                'is_active' => true,
                'permissions' => ['dashboard:view']
            ]);

            UserMainNav::create([
                'user_id' => $adminUser->id,
                'key' => 'quick_actions',
                'title' => [
                    'en' => 'Quick Actions',
                    'ar' => 'إجراءات سريعة'
                ],
                'icon' => 'Zap',
                'route' => '/quick-actions',
                'sort_order' => 2,
                'is_active' => true,
                'permissions' => ['admin:access']
            ]);
        }

        // Get the staff user
        $staffUser = User::where('email', 'staff@staff.com')->first();
        
        if ($staffUser) {
            // Create some example custom navigation items for the staff user
            UserMainNav::create([
                'user_id' => $staffUser->id,
                'key' => 'my_orders',
                'title' => [
                    'en' => 'My Orders',
                    'ar' => 'طلباتي'
                ],
                'icon' => 'ShoppingCart',
                'route' => '/my-orders',
                'sort_order' => 1,
                'is_active' => true,
                'permissions' => ['order:view']
            ]);
        }
    }
}
