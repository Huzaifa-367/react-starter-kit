<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            \App\Http\Middleware\SetSecurityHeaders::class,
            \App\Http\Middleware\SanitizeInput::class,
            \App\Http\Middleware\IpFirewall::class,
            \App\Http\Middleware\LoadSettingsFromDatabase::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureNotSuspended::class,
            \App\Http\Middleware\TrackImpersonation::class,

            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\CheckRoutePermission::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
            'webhooks/email/bounce',
        ]);

        $middleware->alias([
            'subscribed' => \App\Http\Middleware\VerifyActiveSubscription::class,
            'feature' => \App\Http\Middleware\CheckFeatureLimit::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'verified-tos' => \App\Http\Middleware\VerifyTosAccepted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
