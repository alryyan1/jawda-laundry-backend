<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MainNav;

class MainNavSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainNavs = [
            ['title' => 'Dashboard', 'path' => '/', 'icon' => 'LayoutDashboard'],
            ['title' => 'POS', 'path' => '/pos', 'icon' => 'Calculator'],
            ['title' => 'Orders', 'path' => null, 'icon' => 'ShoppingCart'],
            ['title' => 'Customers', 'path' => null, 'icon' => 'Users'],
            ['title' => 'Services', 'path' => null, 'icon' => 'Briefcase'],
            ['title' => 'Expenses', 'path' => null, 'icon' => 'Receipt'],
            ['title' => 'Reports', 'path' => '/reports', 'icon' => 'BarChart3'],
            ['title' => 'Administration', 'path' => null, 'icon' => 'Shield']
        ];

        foreach ($mainNavs as $nav) {
            MainNav::create($nav);
        }
    }
}
