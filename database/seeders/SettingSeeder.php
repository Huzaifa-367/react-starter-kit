<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // SMTP Settings
            [
                'key' => 'mail_host',
                'value' => 'sandbox.smtp.mailtrap.io',
                'group' => 'smtp',
                'type' => 'string',
                'label' => 'Mail Host',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'mail_port',
                'value' => '2525',
                'group' => 'smtp',
                'type' => 'integer',
                'label' => 'Mail Port',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'mail_username',
                'value' => '',
                'group' => 'smtp',
                'type' => 'string',
                'label' => 'Mail Username',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'mail_password',
                'value' => '',
                'group' => 'smtp',
                'type' => 'secret',
                'label' => 'Mail Password',
                'is_encrypted' => true,
                'is_public' => false,
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'group' => 'smtp',
                'type' => 'string',
                'label' => 'Mail Encryption',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'no-reply@example.com',
                'group' => 'smtp',
                'type' => 'string',
                'label' => 'Mail From Address',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_name',
                'value' => 'SaaS App',
                'group' => 'smtp',
                'type' => 'string',
                'label' => 'Mail From Name',
                'is_encrypted' => false,
                'is_public' => false,
            ],

            // Green API Settings
            [
                'key' => 'green_api_url',
                'value' => 'https://api.green-api.com',
                'group' => 'green_api',
                'type' => 'string',
                'label' => 'Green API URL',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'green_api_id_instance',
                'value' => '',
                'group' => 'green_api',
                'type' => 'string',
                'label' => 'Green API Instance ID',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'green_api_token_instance',
                'value' => '',
                'group' => 'green_api',
                'type' => 'secret',
                'label' => 'Green API Token Instance',
                'is_encrypted' => true,
                'is_public' => false,
            ],

            // Stripe Settings
            [
                'key' => 'stripe_key',
                'value' => '',
                'group' => 'stripe',
                'type' => 'string',
                'label' => 'Stripe Publishable Key',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'stripe_secret',
                'value' => '',
                'group' => 'stripe',
                'type' => 'secret',
                'label' => 'Stripe Secret Key',
                'is_encrypted' => true,
                'is_public' => false,
            ],
            [
                'key' => 'stripe_webhook_secret',
                'value' => '',
                'group' => 'stripe',
                'type' => 'secret',
                'label' => 'Stripe Webhook Secret',
                'is_encrypted' => true,
                'is_public' => false,
            ],

            // Firebase Settings
            [
                'key' => 'firebase_project_id',
                'value' => '',
                'group' => 'firebase',
                'type' => 'string',
                'label' => 'Firebase Project ID',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'firebase_private_key_id',
                'value' => '',
                'group' => 'firebase',
                'type' => 'string',
                'label' => 'Firebase Private Key ID',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'firebase_private_key',
                'value' => '',
                'group' => 'firebase',
                'type' => 'secret',
                'label' => 'Firebase Private Key',
                'is_encrypted' => true,
                'is_public' => false,
            ],
            [
                'key' => 'firebase_client_email',
                'value' => '',
                'group' => 'firebase',
                'type' => 'string',
                'label' => 'Firebase Client Email',
                'is_encrypted' => false,
                'is_public' => false,
            ],

            // App Settings
            [
                'key' => 'app_name',
                'value' => 'SaaS App',
                'group' => 'app',
                'type' => 'string',
                'label' => 'App Name',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'app_support_email',
                'value' => 'support@example.com',
                'group' => 'app',
                'type' => 'string',
                'label' => 'App Support Email',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'app_logo_url',
                'value' => '/images/logo.png',
                'group' => 'app',
                'type' => 'string',
                'label' => 'App Logo URL',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'app_currency',
                'value' => 'USD',
                'group' => 'app',
                'type' => 'string',
                'label' => 'App Currency',
                'is_encrypted' => false,
                'is_public' => true,
            ],

            // OTP Settings
            [
                'key' => 'otp_default_channels',
                'value' => '["email"]',
                'group' => 'otp',
                'type' => 'json',
                'label' => 'OTP Default Channels',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'otp_expiry_minutes',
                'value' => '10',
                'group' => 'otp',
                'type' => 'integer',
                'label' => 'OTP Expiry Minutes',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'otp_max_attempts',
                'value' => '5',
                'group' => 'otp',
                'type' => 'integer',
                'label' => 'OTP Max Attempts',
                'is_encrypted' => false,
                'is_public' => false,
            ],
        ];

        foreach ($settings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }
    }
}
