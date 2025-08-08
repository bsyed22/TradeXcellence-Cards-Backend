<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'dashboard',
            'users',
            'cards',
            'card-holders',
            'cards-holder-create',
            'cards-holder-view',
            'cards-create',
            'cards-view',
            'cards-load',
            'cards-unload',
            'cards-transaction-history',
            'roles',
            'profile',
            'settings',
            'reports',
            'notifications'
        ];


        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
