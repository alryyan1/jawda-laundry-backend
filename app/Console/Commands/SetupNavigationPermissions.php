<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\NavigationItem;
use Spatie\Permission\Models\Role;
use Database\Seeders\UserNavigationPermissionSeeder;

class SetupNavigationPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'navigation:setup-permissions {--user-id= : Specific user ID to set up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up navigation permissions for users based on their roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up navigation permissions...');

        $userId = $this->option('user-id');

        if ($userId) {
            // Set up for specific user
            $user = User::findOrFail($userId);

            $this->info("Setting up navigation permissions for user: {$user->name} ({$user->email})");
            UserNavigationPermissionSeeder::setupDefaultNavigationForUser($user);
            $this->info('Navigation permissions set up successfully!');
        } else {
            // Set up for all users
            $users = User::with('roles')->get();
            
            $this->info("Found {$users->count()} users to process...");

            foreach ($users as $user) {
                $this->info("Processing user: {$user->name} ({$user->email})");
                UserNavigationPermissionSeeder::setupDefaultNavigationForUser($user);
            }

            $this->info('Navigation permissions set up successfully for all users!');
        }

        return 0;
    }
}
