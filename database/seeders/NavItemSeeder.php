<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NavItem;

class NavItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $navItems = [
            ['title' => 'Dashboard', 'icon' => 'LayoutDashboard', 'path' => '/'],
            ['title' => 'POS', 'icon' => 'Calculator', 'path' => '/pos'],
            ['title' => 'Orders', 'icon' => 'ShoppingCart', 'path' => null],
            ['title' => 'Customers', 'icon' => 'Users', 'path' => null],
            ['title' => 'Services', 'icon' => 'Briefcase', 'path' => null],
            ['title' => 'Expenses', 'icon' => 'Receipt', 'path' => null],
            ['title' => 'Reports', 'icon' => 'BarChart3', 'path' => '/reports'],
            ['title' => 'Administration', 'icon' => 'Shield', 'path' => null],
            ['title' => 'All Orders', 'icon' => 'List', 'path' => '/orders'],
            ['title' => 'New Order', 'icon' => 'Plus', 'path' => '/orders/new'],
            ['title' => 'All Customers', 'icon' => 'Users', 'path' => '/customers'],
            ['title' => 'Add Customer', 'icon' => 'UserPlus', 'path' => '/customers/new'],
            ['title' => 'Product Categories', 'icon' => 'Grid3x3', 'path' => '/admin/product-categories'],
            ['title' => 'Product Types', 'icon' => 'Tags', 'path' => '/admin/product-types'],
            ['title' => 'Service Actions', 'icon' => 'Zap', 'path' => '/admin/service-actions'],
            ['title' => 'All Expenses', 'icon' => 'Receipt', 'path' => '/expenses'],
            ['title' => 'Expense Categories', 'icon' => 'FolderOpen', 'path' => '/admin/expense-categories'],
            ['title' => 'Sales Reports', 'icon' => 'TrendingUp', 'path' => '/reports/sales'],
            ['title' => 'Cost Reports', 'icon' => 'TrendingDown', 'path' => '/reports/costs'],
            ['title' => 'Orders Report', 'icon' => 'FileText', 'path' => '/reports/orders'],
            ['title' => 'Daily Revenue', 'icon' => 'Calendar', 'path' => '/reports/daily-revenue'],
            ['title' => 'Daily Costs', 'icon' => 'CalendarX', 'path' => '/reports/daily-costs'],
            ['title' => 'Detailed Reports', 'icon' => 'FileBarChart', 'path' => '/reports/detailed'],
            ['title' => 'Users Management', 'icon' => 'Users', 'path' => '/admin/users'],
            ['title' => 'Roles & Permissions', 'icon' => 'Shield', 'path' => '/admin/roles'],
            ['title' => 'Navigation Management', 'icon' => 'Navigation', 'path' => '/admin/navigation'],
            ['title' => 'System Settings', 'icon' => 'Settings', 'path' => '/settings'],
        ];

        foreach ($navItems as $item) {
            NavItem::create($item);
        }
    }
}
