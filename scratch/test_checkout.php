<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Billing\StripeBillingController;

$user = User::where('email', 'ulc320@gmail.com')->first();
if (!$user) {
    echo "User not found!" . PHP_EOL;
    exit;
}

Auth::login($user);

$request = Request::create('/billing/checkout', 'POST', [
    'plan_id' => 1,
    'billing_cycle' => 'monthly',
]);
$request->setUserResolver(fn() => $user);
$request->headers->set('Accept', 'application/json');

$controller = new StripeBillingController();

try {
    $response = $controller->checkoutSession($request);
    echo "Status code: " . $response->getStatusCode() . PHP_EOL;
    echo "Content: " . $response->getContent() . PHP_EOL;
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
