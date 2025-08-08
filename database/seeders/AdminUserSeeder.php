<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create admin role if not exists
        $adminRole = Role::where(['name' => 'admin'])->first();

        // 2. Get all permissions
        $permissions = Permission::all();

        // 3. Assign all permissions to the admin role
        $adminRole->syncPermissions($permissions);

        // 4. Create the admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@cards.tradexcellence.co.uk'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 5. Assign the admin role to the user
        $adminUser->assignRole($adminRole);

        $this->command->info('Admin user and role seeded successfully.');
    }
}
