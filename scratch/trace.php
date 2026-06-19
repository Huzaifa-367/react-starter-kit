<?php

define('LARAVEL_START', microtime(true));

putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('APP_ENV=testing');

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Instantiate the test class
$test = new class('test_five_failed_otp_attempts_lockout_user') extends \Tests\Feature\SubscriptionTest {
    public function runTestCode()
    {
        $this->setUp();
        $this->withoutExceptionHandling();
        $this->app->instance(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, new class {
            public function handle($request, $next) { return $next($request); }
        });
        $sub = \App\Models\Subscription::create([
            'user_id' => $this->user->id,
            'subscribable_type' => User::class,
            'subscribable_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
            'name' => 'main',
            'status' => 'active',
            'ends_at' => now()->addDays(20),
            'auto_renew' => true,
        ]);

        $this->user->assignRole('User (Subscribed)');
        $this->user->removeRole('User (Free)');
        $this->user->refresh();

        $this->actingAs($this->user);

        echo "Sending cancel subscription POST...\n";
        $response = $this->post('/billing/cancel');
        echo "Response status: " . $response->status() . "\n";
        
        $sessionErrors = $response->session()->get('errors');
        if ($sessionErrors) {
            echo "Session errors: " . json_encode($sessionErrors->getBag('default')->getMessages()) . "\n";
        } else {
            echo "No session errors.\n";
        }

        $sub->refresh();
        echo "Sub status: " . $sub->status . "\n";
    }
};

try {
    $test->runTestCode();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
