<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$app->make(ConsoleKernel::class)->bootstrap();
$kernel = $app->make(HttpKernel::class);

try {
    \Illuminate\Support\Facades\DB::beginTransaction();

    \App\Models\Setting::flush();

    $user = User::create([
        'name' => 'Suspended User',
        'email' => 'suspended@example.com',
        'password' => Hash::make('password'),
        'is_suspended' => true,
        'suspended_at' => now(),
        'suspended_reason' => 'Violation of terms',
    ]);

    $request = Request::create('/login', 'POST', [
        'email' => 'suspended@example.com',
        'password' => 'password',
    ]);

    $response = $kernel->handle($request);

    if (isset($response->exception) && $response->exception) {
        throw $response->exception;
    }

} catch (\Throwable $e) {
    echo "ERROR CLASS: " . get_class($e) . "\n";
    echo "MESSAGE: " . $e->getMessage() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
} finally {
    \Illuminate\Support\Facades\DB::rollBack();
}
