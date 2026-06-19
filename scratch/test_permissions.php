<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Helper to run permission check
function checkPermission($user, $permission) {
    // Reset/clear cached permissions/roles on the user object to avoid caching interference
    $user->unsetRelation('roles');
    $user->unsetRelation('permissions');
    
    return $user->can($permission) ? "ALLOWED" : "DENIED";
}

// 1. Fetch or create users for testing
$superAdmin = User::firstOrCreate(
    ['email' => 'superadmin_test@example.com'],
    ['name' => 'Super Admin Test', 'password' => Hash::make('password')]
);
$superAdmin->syncRoles(['Super Admin']);

$admin = User::firstOrCreate(
    ['email' => 'admin_test@example.com'],
    ['name' => 'Admin Test', 'password' => Hash::make('password')]
);
$admin->syncRoles(['Admin']);

$subscribedUser = User::firstOrCreate(
    ['email' => 'subscribed_test@example.com'],
    ['name' => 'Subscribed User Test', 'password' => Hash::make('password')]
);
$subscribedUser->syncRoles(['User (Subscribed)']);

$freeUser = User::firstOrCreate(
    ['email' => 'free_test@example.com'],
    ['name' => 'Free User Test', 'password' => Hash::make('password')]
);
$freeUser->syncRoles(['User (Free)']);

// Check permissions
$rolesToCheck = [
    'Super Admin' => $superAdmin,
    'Admin' => $admin,
    'User (Subscribed)' => $subscribedUser,
    'User (Free)' => $freeUser,
];

$permissionsToVerify = [
    // Admin routes
    'admin.dashboard',
    'admin.users.index',
    'admin.users.destroy', // Super Admin exclusive
    'admin.logs.clear',    // Super Admin exclusive
    
    // Billing routes
    'billing.dashboard',
    'billing.portal',
    
    // Profile/Dashboard routes
    'dashboard',
    'profile.phone.edit',
    'profile.edit',
];

echo "============================================================\n";
echo "ROLE PERMISSION VERIFICATION REPORT\n";
echo "============================================================\n";

foreach ($rolesToCheck as $roleName => $user) {
    echo "\nRole: $roleName ($user->email)\n";
    echo str_repeat("-", 40) . "\n";
    foreach ($permissionsToVerify as $permission) {
        $result = checkPermission($user, $permission);
        printf("  %-30s: %s\n", $permission, $result);
    }
}
echo "============================================================\n";

// Cleanup test users
$superAdmin->delete();
$admin->delete();
$subscribedUser->delete();
$freeUser->delete();
?>
