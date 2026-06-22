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

        // 1. Admin Permissions (except Super Admin exclusives)
        $adminPermissions = [
            'admin.dashboard',
            'admin.analytics',
            'admin.analytics.export',
            'admin.users.index',
            'admin.users.trashed',
            'admin.users.show',
            'admin.users.suspend',
            'admin.users.unsuspend',
            'admin.users.assign-role',
            'admin.users.assign-plan',
            'admin.users.impersonate',
            'admin.users.notes.store',
            'admin.users.notes.destroy',
            'admin.users.restore',
            'admin.users.bulk',
            'admin.users.export',
            'admin.impersonation.stop',
            'admin.roles.index',
            'admin.roles.store',
            'admin.roles.update',
            'admin.roles.sync',
            'admin.roles.destroy',
            'admin.plans.index',
            'admin.plans.store',
            'admin.plans.update',
            'admin.plans.features.sync',
            'admin.plans.toggle',
            'admin.plans.destroy',
            'admin.invitations.index',
            'admin.invitations.store',
            'admin.invitations.resend',
            'admin.invitations.cancel',
            'admin.coupons.index',
            'admin.coupons.store',
            'admin.coupons.show',
            'admin.coupons.update',
            'admin.coupons.destroy',
            'admin.coupons.toggle',
            'admin.segments.index',
            'admin.segments.store',
            'admin.segments.update',
            'admin.segments.preview',
            'admin.segments.destroy',
            'admin.segments.export',
            'admin.segments.notify',
            'admin.broadcasts.index',
            'admin.broadcasts.store',
            'admin.broadcasts.send',
            'admin.broadcasts.preview',
            'admin.broadcasts.destroy',
            'admin.email-templates.index',
            'admin.email-templates.edit',
            'admin.email-templates.update',
            'admin.email-templates.preview',
            'admin.email-templates.test',
            'admin.feature-flags.index',
            'admin.feature-flags.store',
            'admin.feature-flags.show',
            'admin.feature-flags.update',
            'admin.feature-flags.destroy',
            'admin.ip-rules.index',
            'admin.ip-rules.store',
            'admin.ip-rules.show',
            'admin.ip-rules.update',
            'admin.ip-rules.destroy',
            'admin.settings',
            'admin.settings.update',
            'admin.maintenance.enable',
            'admin.maintenance.disable',
            'admin.system-health',
            'admin.cache.flush',
            'admin.failed-jobs.index',
            'admin.failed-jobs.retry',
            'admin.failed-jobs.retry-all',
            'admin.failed-jobs.destroy',
            'admin.failed-jobs.flush',
            'admin.rate-limits.index',
            'admin.rate-limits.unlock',
            'admin.webhook-logs.index',
            'admin.webhook-logs.show',
            'admin.webhook-logs.reprocess',
            'admin.logs.index',
            'admin.logs.show',
            'admin.logs.download',
            'admin.activity.index',
            'admin.activity.export',
            'admin.diagnostics.email',
            'admin.diagnostics.email.preview',
            'admin.diagnostics.fcm',
            'admin.diagnostics.fcm.broadcast',
            'admin.diagnostics.whatsapp',
            'admin.diagnostics.sms',
        ];

        // 2. Super Admin Exclusives
        $superAdminExclusives = [
            'admin.users.destroy',
            'admin.logs.clear',
        ];

        // 3. Billing Permissions
        $billingPermissions = [
            'billing.dashboard',
            'billing.portal',
            'billing.cancel',
            'billing.resume',
            'billing.change-plan',
            'billing.invoice.download',
        ];

        // 4. Profile & Common User Permissions
        $profilePermissions = [
            'dashboard',
            'pricing.subscribed',
            'profile.phone.edit',
            'profile.phone.update',
            'profile.avatar.update',
            'profile.avatar.delete',
            'profile.deletion.confirm',
            'profile.export',
            'profile.login-history',
            'profile.referrals',
            'profile.deletion.request',
            'profile.sessions.index',
            'profile.sessions.destroy-others',
            'profile.sessions.destroy',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'security.edit',
            'user-password.update',
            'appearance.edit',
        ];

        // Combine all permissions to register in DB
        $allPermissions = array_merge(
            $adminPermissions,
            $superAdminExclusives,
            $billingPermissions,
            $profilePermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $subscribedRole = Role::firstOrCreate(['name' => 'User (Subscribed)', 'guard_name' => 'web']);
        $freeRole = Role::firstOrCreate(['name' => 'User (Free)', 'guard_name' => 'web']);

        // Sync to Super Admin
        $superAdminRole->syncPermissions(Permission::all());

        // Sync to Admin
        $adminRolePermissions = array_merge(
            $adminPermissions,
            $billingPermissions,
            $profilePermissions
        );
        $adminRole->syncPermissions($adminRolePermissions);

        // Sync to User (Subscribed)
        $subscribedRolePermissions = array_merge(
            $billingPermissions,
            $profilePermissions
        );
        $subscribedRole->syncPermissions($subscribedRolePermissions);

        // Sync to User (Free)
        $freeRolePermissions = array_merge(
            $billingPermissions,
            $profilePermissions
        );
        $freeRole->syncPermissions($freeRolePermissions);
    }
}
