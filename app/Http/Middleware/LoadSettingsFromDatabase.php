<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class LoadSettingsFromDatabase
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mapping = [
            'mail_host'                 => 'mail.mailers.smtp.host',
            'mail_port'                 => 'mail.mailers.smtp.port',
            'mail_username'             => 'mail.mailers.smtp.username',
            'mail_password'             => 'mail.mailers.smtp.password',
            'mail_encryption'           => 'mail.mailers.smtp.encryption',
            'mail_from_address'         => 'mail.from.address',
            'mail_from_name'            => 'mail.from.name',
            'green_api_url'             => 'services.green_api.url',
            'green_api_id_instance'     => 'services.green_api.id_instance',
            'green_api_token_instance'  => 'services.green_api.token_instance',
            'stripe_key'                => 'services.stripe.key',
            'stripe_secret'             => 'services.stripe.secret',
            'stripe_webhook_secret'     => 'services.stripe.webhook_secret',
            'firebase_project_id'       => 'notifire.project_id',
            'firebase_private_key'      => 'notifire.private_key',
            'firebase_client_email'     => 'notifire.client_email',
            'twilio_account_sid'        => 'services.twilio.sid',
            'twilio_auth_token'         => 'services.twilio.token',
            'twilio_from_number'        => 'services.twilio.from',
        ];

        $configData = [];
        foreach ($mapping as $settingKey => $configPath) {
            $value = Setting::get($settingKey);
            if ($value !== null) {
                $configData[$configPath] = $value;
            }
        }

        if (!empty($configData)) {
            config($configData);
        }

        return $next($request);
    }
}
