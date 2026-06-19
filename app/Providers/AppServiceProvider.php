<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\GreenApi\RestApi\GreenApiClient::class, function ($app) {
            $url = \App\Models\Setting::get('green_api_url') ?: config('services.green_api.url');
            $mediaUrl = \App\Models\Setting::get('green_api_media_url') ?: config('services.green_api.media_url');
            $idInstance = \App\Models\Setting::get('green_api_id_instance') ?: config('services.green_api.id_instance');
            $tokenInstance = \App\Models\Setting::get('green_api_token_instance') ?: config('services.green_api.token_instance');

            return new \GreenApi\RestApi\GreenApiClient(
                $idInstance,
                $tokenInstance,
                null,
                $url ?: "https://api.green-api.com",
                $mediaUrl ?: "https://media.green-api.com"
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
