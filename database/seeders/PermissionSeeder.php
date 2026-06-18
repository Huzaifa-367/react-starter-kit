<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // All 13 permissions
        $permissions = [
            'view_admin_dashboard',
            'manage_users',
            'suspend_users',
            'delete_users',
            'manage_roles',
            'manage_plans',
            'manage_features',
            'manage_invitations',
            'manage_settings',
            'send_fcm_notifications',
            'send_test_emails',
            'send_test_whatsapp',
            'view_notification_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'User (Subscribed)', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'User (Free)', 'guard_name' => 'web']);

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions(Permission::all());

        // Assign all except delete_users to Admin
        $adminPermissions = Permission::where('name', '!=', 'delete_users')->get();
        $admin->syncPermissions($adminPermissions);
    }
}
