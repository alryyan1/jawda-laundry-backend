<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NavigationItem;

class NavigationItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Routes are based on jawda-laundry-front/src/router/index.tsx
     * All routes are relative to the base path and exclude authentication routes
     */
    public function run(): void
    {
        $navigationItems = [
            [
                'key' => 'dashboard',
                'title' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم'],
                'icon' => 'LayoutDashboard',
                'route' => '/', // Root path maps to DashboardPage
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['dashboard:view']
            ],
            [
                'key' => 'pos',
                'title' => ['en' => 'POS', 'ar' => 'نقطة البيع'],
                'icon' => 'Calculator',
                'route' => '/pos',
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['pos:access']
            ],
            [
                'key' => 'orders',
                'title' => ['en' => 'Orders', 'ar' => 'الطلبات'],
                'icon' => 'ShoppingCart',
                'route' => null, // Parent item - no direct route
                'sort_order' => 3,
                'is_default' => true,
                'permissions' => ['order:view']
            ],
            [
                'key' => 'customers',
                'title' => ['en' => 'Customers', 'ar' => 'العملاء'],
                'icon' => 'Users',
                'route' => null, // Parent item - no direct route
                'sort_order' => 4,
                'is_default' => true,
                'permissions' => ['customer:view']
            ],
            [
                'key' => 'services',
                'title' => ['en' => 'Services', 'ar' => 'الخدمات'],
                'icon' => 'Briefcase',
                'route' => null, // Parent item - no direct route
                'sort_order' => 5,
                'is_default' => true,
                'permissions' => ['service:view']
            ],
            [
                'key' => 'expenses',
                'title' => ['en' => 'Expenses', 'ar' => 'المصروفات'],
                'icon' => 'Receipt',
                'route' => null, // Parent item - no direct route
                'sort_order' => 6,
                'is_default' => true,
                'permissions' => ['expense:view']
            ],
            [
                'key' => 'reports',
                'title' => ['en' => 'Reports', 'ar' => 'التقارير'],
                'icon' => 'BarChart3',
                'route' => '/reports', // Main reports page
                'sort_order' => 7,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'admin',
                'title' => ['en' => 'Administration', 'ar' => 'الإدارة'],
                'icon' => 'Shield',
                'route' => null, // Parent item - no direct route
                'sort_order' => 8,
                'is_default' => true,
                'permissions' => ['admin:access']
            ]
        ];

        // Create main navigation items first
        $createdItems = [];
        foreach ($navigationItems as $item) {
            $createdItems[$item['key']] = NavigationItem::firstOrCreate(
                ['key' => $item['key']], 
                $item
            );
        }

        // Sub-navigation items based on actual routes in index.tsx
        $subNavItems = [
            // Orders sub-items (path: "orders/*")
            [
                'key' => 'orders_list',
                'title' => ['en' => 'All Orders', 'ar' => 'جميع الطلبات'],
                'icon' => 'List',
                'route' => '/orders',
                'parent_id' => $createdItems['orders']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['order:view']
            ],
            [
                'key' => 'orders_new',
                'title' => ['en' => 'New Order', 'ar' => 'طلب جديد'],
                'icon' => 'Plus',
                'route' => '/orders/new',
                'parent_id' => $createdItems['orders']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['order:create']
            ],
            
            // Customers sub-items (path: "customers/*")
            [
                'key' => 'customers_list',
                'title' => ['en' => 'All Customers', 'ar' => 'جميع العملاء'],
                'icon' => 'Users',
                'route' => '/customers',
                'parent_id' => $createdItems['customers']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['customer:view']
            ],
            [
                'key' => 'customers_new',
                'title' => ['en' => 'Add Customer', 'ar' => 'إضافة عميل'],
                'icon' => 'UserPlus',
                'route' => '/customers/new',
                'parent_id' => $createdItems['customers']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['customer:create']
            ],
            
            // Services sub-items (admin/product-*)
            [
                'key' => 'services_categories',
                'title' => ['en' => 'Product Categories', 'ar' => 'فئات المنتجات'],
                'icon' => 'Grid3x3',
                'route' => '/admin/product-categories',
                'parent_id' => $createdItems['services']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['product-category:view']
            ],
            [
                'key' => 'services_types',
                'title' => ['en' => 'Product Types', 'ar' => 'أنواع المنتجات'],
                'icon' => 'Tags',
                'route' => '/admin/product-types',
                'parent_id' => $createdItems['services']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['product-type:view']
            ],
            [
                'key' => 'services_actions',
                'title' => ['en' => 'Service Actions', 'ar' => 'إجراءات الخدمة'],
                'icon' => 'Zap',
                'route' => '/admin/service-actions',
                'parent_id' => $createdItems['services']->id,
                'sort_order' => 3,
                'is_default' => true,
                'permissions' => ['service-action:view']
            ],
            
            // Expenses sub-items (path: "expenses", "admin/expense-categories")
            [
                'key' => 'expenses_list',
                'title' => ['en' => 'All Expenses', 'ar' => 'جميع المصروفات'],
                'icon' => 'Receipt',
                'route' => '/expenses',
                'parent_id' => $createdItems['expenses']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['expense:view']
            ],
            [
                'key' => 'expenses_categories',
                'title' => ['en' => 'Expense Categories', 'ar' => 'فئات المصروفات'],
                'icon' => 'FolderOpen',
                'route' => '/admin/expense-categories',
                'parent_id' => $createdItems['expenses']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['expense-category:view']
            ],
            
            // Reports sub-items (path: "reports/*")
            [
                'key' => 'reports_sales',
                'title' => ['en' => 'Sales Reports', 'ar' => 'تقارير المبيعات'],
                'icon' => 'TrendingUp',
                'route' => '/reports/sales',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'reports_costs',
                'title' => ['en' => 'Cost Reports', 'ar' => 'تقارير التكاليف'],
                'icon' => 'TrendingDown',
                'route' => '/reports/costs',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'reports_orders',
                'title' => ['en' => 'Orders Report', 'ar' => 'تقرير الطلبات'],
                'icon' => 'FileText',
                'route' => '/reports/orders',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 3,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'reports_daily_revenue',
                'title' => ['en' => 'Daily Revenue', 'ar' => 'الإيرادات اليومية'],
                'icon' => 'Calendar',
                'route' => '/reports/daily-revenue',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 4,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'reports_daily_costs',
                'title' => ['en' => 'Daily Costs', 'ar' => 'التكاليف اليومية'],
                'icon' => 'CalendarX',
                'route' => '/reports/daily-costs',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 5,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            [
                'key' => 'reports_detailed',
                'title' => ['en' => 'Detailed Reports', 'ar' => 'التقارير المفصلة'],
                'icon' => 'FileBarChart',
                'route' => '/reports/detailed',
                'parent_id' => $createdItems['reports']->id,
                'sort_order' => 6,
                'is_default' => true,
                'permissions' => ['report:view']
            ],
            
            // Admin sub-items (path: "admin/*", "settings")
            [
                'key' => 'admin_users',
                'title' => ['en' => 'Users Management', 'ar' => 'إدارة المستخدمين'],
                'icon' => 'Users',
                'route' => '/admin/users',
                'parent_id' => $createdItems['admin']->id,
                'sort_order' => 1,
                'is_default' => true,
                'permissions' => ['user:view']
            ],
            [
                'key' => 'admin_roles',
                'title' => ['en' => 'Roles & Permissions', 'ar' => 'الأدوار والصلاحيات'],
                'icon' => 'Shield',
                'route' => '/admin/roles',
                'parent_id' => $createdItems['admin']->id,
                'sort_order' => 2,
                'is_default' => true,
                'permissions' => ['role:view']
            ],
            [
                'key' => 'admin_navigation',
                'title' => ['en' => 'Navigation Management', 'ar' => 'إدارة التنقل'],
                'icon' => 'Navigation',
                'route' => '/admin/navigation',
                'parent_id' => $createdItems['admin']->id,
                'sort_order' => 3,
                'is_default' => true,
                'permissions' => ['navigation:view']
            ],
            [
                'key' => 'admin_settings',
                'title' => ['en' => 'System Settings', 'ar' => 'إعدادات النظام'],
                'icon' => 'Settings',
                'route' => '/settings',
                'parent_id' => $createdItems['admin']->id,
                'sort_order' => 4,
                'is_default' => true,
                'permissions' => ['setting:manage']
            ]
        ];

        // Create sub-navigation items
        foreach ($subNavItems as $subItem) {
            NavigationItem::firstOrCreate(
                ['key' => $subItem['key']], 
                $subItem
            );
        }
    }
} 