# Navigation Permissions System

This document explains how the navigation permissions system works in the Jawda Laundry application.

## Overview

The navigation permissions system controls which navigation items (menu items) each user can see based on their role. This ensures that users only see the parts of the application they need to access.

## Role-Based Navigation Access

### Admin Role
- **Access**: All navigation items
- **Includes**: Dashboard, POS, Orders, Customers, Services, Dining, Expenses, Purchases, Suppliers, Reports, Administration

### Receptionist Role
- **Access**: POS, Orders, Expenses
- **Includes**: 
  - POS main page
  - Orders (All Orders, New Order, Kanban Board)
  - Expenses (All Expenses)

### Processor Role (Washer/Ironer)
- **Access**: Orders only
- **Includes**: Orders (All Orders, Kanban Board)

### Delivery Role
- **Access**: Orders only
- **Includes**: Orders (All Orders, Kanban Board)

## How It Works

### 1. Navigation Items
Navigation items are defined in `NavigationItemSeeder.php` and stored in the `navigation_items` table. Each item has:
- A unique key (e.g., 'pos', 'orders', 'admin')
- Title in multiple languages
- Route path
- Required permissions
- Parent-child relationships

### 2. User Navigation Permissions
User-specific navigation permissions are stored in the `user_navigation_permissions` table with:
- `user_id`: The user
- `navigation_item_id`: The navigation item
- `is_granted`: Boolean indicating if the user can access this item

### 3. Permission Logic
The system checks permissions in this order:
1. **Explicit User Permission**: If a user has a specific permission set in `user_navigation_permissions`
2. **Role-Based Permission**: If no explicit permission, check if the user's role has the required permissions
3. **Default Access**: If no permissions are required, allow access

## Setup Instructions

### 1. Run the Seeders
```bash
php artisan db:seed
```

This will:
- Create navigation items
- Set up roles and permissions
- Assign navigation permissions to existing users

### 2. Set Up Navigation for New Users
Navigation permissions are automatically set up when:
- A new user is created
- A user's role is changed

### 3. Manual Setup
To set up navigation permissions for existing users:

```bash
# Set up for all users
php artisan navigation:setup-permissions

# Set up for a specific user
php artisan navigation:setup-permissions --user-id=1
```

### 4. Test the Setup
```bash
php test_navigation_setup.php
```

## Database Tables

### navigation_items
- `id`: Primary key
- `key`: Unique identifier (e.g., 'pos', 'orders')
- `title`: JSON with translations
- `icon`: Icon name
- `route`: Route path
- `parent_id`: Parent navigation item
- `sort_order`: Display order
- `is_active`: Whether item is active
- `is_default`: Whether item is shown by default
- `permissions`: JSON array of required permissions

### user_navigation_permissions
- `user_id`: Foreign key to users table
- `navigation_item_id`: Foreign key to navigation_items table
- `is_granted`: Boolean permission flag
- `created_at`, `updated_at`: Timestamps

## Adding New Navigation Items

1. Add the navigation item to `NavigationItemSeeder.php`
2. Run the seeder: `php artisan db:seed --class=NavigationItemSeeder`
3. Update role permissions in `PermissionSeeder.php` if needed
4. Set up user navigation permissions: `php artisan navigation:setup-permissions`

## Frontend Integration

The frontend should:
1. Fetch user's accessible navigation items via API
2. Only display navigation items the user can access
3. Handle permission checks for individual actions

## Troubleshooting

### Common Issues

1. **User sees no navigation items**
   - Check if user has a role assigned
   - Verify navigation permissions are set up
   - Run `php artisan navigation:setup-permissions --user-id=<user_id>`

2. **Navigation items not updating after role change**
   - The system should automatically update permissions
   - Manually run the setup command if needed

3. **New user has no navigation access**
   - Check if the user creation process is working
   - Verify the User model's boot method is being called

### Debug Commands

```bash
# Check user roles
php artisan tinker
>>> User::with('roles')->get()->each(fn($u) => echo "{$u->name}: " . $u->roles->pluck('name')->join(', ') . "\n");

# Check navigation permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->navigationItems()->get()->each(fn($n) => echo "{$n->key}: " . ($n->pivot->is_granted ? 'GRANTED' : 'DENIED') . "\n");
```

## Security Notes

- Navigation permissions are enforced on both frontend and backend
- Always verify permissions on the backend for API endpoints
- The frontend should not be trusted for permission enforcement
- Regular audits of user permissions are recommended
