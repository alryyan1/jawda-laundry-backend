<?php

namespace App\Services;

class NavigationService
{
    /**
     * Get navigation items based on user role
     */
    public function getNavigationItems(string $role): array
    {
        if ($role === 'admin') {
            return $this->getAdminNavigation();
        } elseif ($role === 'staff') {
            return $this->getStaffNavigation();
        }
        
        return [];
    }

    /**
     * Get admin navigation - shows all routes
     */
    private function getAdminNavigation(): array
    {
        return [
            [
                'key' => 'dashboard',
                'title' => ['en' => 'Dashboard', 'ar' => 'لوحة التحكم'],
                'icon' => 'LayoutDashboard',
                'route' => '/',
                'sort_order' => 1,
            ],
            [
                'key' => 'pos',
                'title' => ['en' => 'POS', 'ar' => 'نقطة البيع'],
                'icon' => 'Calculator',
                'route' => '/pos',
                'sort_order' => 2,
            ],
            [
                'key' => 'kitchen',
                'title' => ['en' => 'Kitchen', 'ar' => 'المطبخ'],
                'icon' => 'ChefHat',
                'route' => '/kitchen',
                'sort_order' => 3,
            ],
            [
                'key' => 'orders',
                'title' => ['en' => 'Orders', 'ar' => 'الطلبات'],
                'icon' => 'ShoppingCart',
                'route' => '/orders',
                'sort_order' => 4,
            ],
            [
                'key' => 'customers',
                'title' => ['en' => 'Customers', 'ar' => 'العملاء'],
                'icon' => 'Users',
                'route' => '/customers',
                'sort_order' => 5,
            ],
            [
                'key' => 'services',
                'title' => ['en' => 'Services', 'ar' => 'الخدمات'],
                'icon' => 'Briefcase',
                'route' => null, // No direct route, it's a parent
                'sort_order' => 6,
                'children' => [
                    [
                        'key' => 'services_categories',
                        'title' => ['en' => 'Product Categories', 'ar' => 'فئات المنتجات'],
                        'icon' => 'Grid3x3',
                        'route' => '/admin/product-categories',
                        'sort_order' => 1,
                    ],
                    [
                        'key' => 'services_types',
                        'title' => ['en' => 'Product Types', 'ar' => 'أنواع المنتجات'],
                        'icon' => 'Tags',
                        'route' => '/admin/product-types',
                        'sort_order' => 2,
                    ],
                    [
                        'key' => 'services_actions',
                        'title' => ['en' => 'Service Actions', 'ar' => 'إجراءات الخدمة'],
                        'icon' => 'Zap',
                        'route' => '/admin/service-actions',
                        'sort_order' => 3,
                    ],
                    [
                        'key' => 'services_offerings',
                        'title' => ['en' => 'Service Offerings', 'ar' => 'عروض الخدمة'],
                        'icon' => 'Package',
                        'route' => '/service-offerings',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'key' => 'expenses',
                'title' => ['en' => 'Expenses', 'ar' => 'المصروفات'],
                'icon' => 'Receipt',
                'route' => '/expenses',
                'sort_order' => 7,
            ],
            [
                'key' => 'reports',
                'title' => ['en' => 'Reports', 'ar' => 'التقارير'],
                'icon' => 'BarChart3',
                'route' => '/reports', // Main reports page
                'sort_order' => 8,
                'children' => [
                    [
                        'key' => 'reports_sales',
                        'title' => ['en' => 'Sales Reports', 'ar' => 'تقارير المبيعات'],
                        'icon' => 'TrendingUp',
                        'route' => '/reports/sales',
                        'sort_order' => 1,
                    ],
                    [
                        'key' => 'reports_costs',
                        'title' => ['en' => 'Cost Reports', 'ar' => 'تقارير التكاليف'],
                        'icon' => 'TrendingDown',
                        'route' => '/reports/costs',
                        'sort_order' => 2,
                    ],
                    [
                        'key' => 'reports_orders',
                        'title' => ['en' => 'Orders Report', 'ar' => 'تقرير الطلبات'],
                        'icon' => 'FileText',
                        'route' => '/reports/orders',
                        'sort_order' => 3,
                    ],
                    [
                        'key' => 'reports_daily_revenue',
                        'title' => ['en' => 'Daily Revenue', 'ar' => 'الإيرادات اليومية'],
                        'icon' => 'Calendar',
                        'route' => '/reports/daily-revenue',
                        'sort_order' => 4,
                    ],
                    [
                        'key' => 'reports_daily_costs',
                        'title' => ['en' => 'Daily Costs', 'ar' => 'التكاليف اليومية'],
                        'icon' => 'CalendarX',
                        'route' => '/reports/daily-costs',
                        'sort_order' => 5,
                    ],
                 
                ],
            ],
            [
                'key' => 'admin',
                'title' => ['en' => 'Administration', 'ar' => 'الإدارة'],
                'icon' => 'Shield',
                'route' => null, // No direct route, it's a parent
                'sort_order' => 9,
                'children' => [
                    [
                        'key' => 'admin_users',
                        'title' => ['en' => 'Users Management', 'ar' => 'إدارة المستخدمين'],
                        'icon' => 'Users',
                        'route' => '/admin/users',
                        'sort_order' => 1,
                    ],
                    [
                        'key' => 'admin_roles',
                        'title' => ['en' => 'Roles & Permissions', 'ar' => 'الأدوار والصلاحيات'],
                        'icon' => 'Shield',
                        'route' => '/admin/roles',
                        'sort_order' => 2,
                    ],
                    [
                        'key' => 'admin_restaurant_tables',
                        'title' => ['en' => 'Restaurant Tables', 'ar' => 'طاولات المطعم'],
                        'icon' => 'Table',
                        'route' => '/admin/restaurant-tables',
                        'sort_order' => 3,
                    ],

                    [
                        'key' => 'admin_settings',
                        'title' => ['en' => 'System Settings', 'ar' => 'إعدادات النظام'],
                        'icon' => 'Settings',
                        'route' => '/settings',
                        'sort_order' => 4,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get staff navigation - shows limited routes
     */
    private function getStaffNavigation(): array
    {
        return [
            [
                'key' => 'kitchen',
                'title' => ['en' => 'Kitchen', 'ar' => 'المطبخ'],
                'icon' => 'ChefHat',
                'route' => '/kitchen',
                'sort_order' => 1,
            ],
            [
                'key' => 'pos',
                'title' => ['en' => 'POS', 'ar' => 'نقطة البيع'],
                'icon' => 'Calculator',
                'route' => '/pos',
                'sort_order' => 2,
            ],
            [
                'key' => 'orders',
                'title' => ['en' => 'Orders', 'ar' => 'الطلبات'],
                'icon' => 'ShoppingCart',
                'route' => '/orders',
                'sort_order' => 3,
            ],
            [
                'key' => 'expenses',
                'title' => ['en' => 'Expenses', 'ar' => 'المصروفات'],
                'icon' => 'Receipt',
                'route' => '/expenses',
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * Get navigation items sorted by sort_order
     */
    public function getSortedNavigationItems(string $role): array
    {
        $items = $this->getNavigationItems($role);
        
        // Sort by sort_order
        usort($items, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });
        
        return $items;
    }
}
