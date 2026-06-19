<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    /**
     * Display settings grouped by group.
     */
    public function index(): Response
    {
        $settings = Setting::all()->groupBy('group')->map(function ($groupSettings) {
            return $groupSettings->map(function ($setting) {
                $value = $setting->getAttributes()['value'] ?? null;
                
                if ($setting->is_encrypted && $value) {
                    try {
                        $value = decrypt($value);
                    } catch (\Exception $e) {
                        // fall back to raw value
                    }
                }

                if ($setting->type === 'secret' && $value) {
                    $value = '••••••••';
                }

                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => $value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'label' => $setting->label,
                    'is_encrypted' => $setting->is_encrypted,
                    'is_public' => $setting->is_public,
                ];
            });
        });

        return Inertia::render('admin/settings/index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the key-value settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $smtpChanged = false;
        $greenApiChanged = false;

        foreach ($request->settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (!$setting) {
                continue;
            }

            if ($setting->type === 'secret' && $value === '••••••••') {
                continue;
            }

            if (str_starts_with($key, 'mail_') && Setting::get($key) !== $value) {
                $smtpChanged = true;
            }

            if (str_starts_with($key, 'green_api_') && Setting::get($key) !== $value) {
                if (in_array($key, ['green_api_id_instance', 'green_api_token_instance', 'green_api_url', 'green_api_media_url'])) {
                    $greenApiChanged = true;
                }
            }

            Setting::set($key, $value);
        }

        Setting::flush();

        if ($greenApiChanged) {
            try {
                $greenApi = new \App\Services\GreenApiService();
                $greenApi->syncSessionSettings();
            } catch (\Exception $e) {
                Log::warning("Failed to auto-sync Green API session settings: " . $e->getMessage());
            }
        }

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Exception $e) {
            Log::error("Failed to clear Artisan cache during settings update: " . $e->getMessage());
        }

        if ($smtpChanged) {
            $testSuccess = $this->testSmtpConnection();
            if (!$testSuccess) {
                return back()->withErrors(['mail_host' => 'SMTP connection test failed. Please verify mail server credentials.']);
            }
        }

        return back()->with('status', 'Settings updated successfully.');
    }

    /**
     * Sync WhatsApp session settings from Green API.
     */
    public function syncWhatsapp(): RedirectResponse
    {
        $greenApi = new \App\Services\GreenApiService();
        $synced = $greenApi->syncSessionSettings();

        if ($synced) {
            return back()->with('status', 'WhatsApp session settings synced successfully.');
        }

        return back()->withErrors(['green_api_id_instance' => 'Failed to sync WhatsApp session. Please verify Green API instance ID, token, and connection.']);
    }

    /**
     * Test connection to configured SMTP mail server using fsockopen.
     */
    private function testSmtpConnection(): bool
    {
        $host = Setting::get('mail_host');
        $port = Setting::get('mail_port');

        if (empty($host) || empty($port)) {
            return false;
        }

        try {
            $connection = @fsockopen($host, (int) $port, $errno, $errstr, 3);
            if (is_resource($connection)) {
                fclose($connection);
                return true;
            }
        } catch (\Exception $e) {
            Log::warning("SMTP connection test exception: " . $e->getMessage());
        }

        return false;
    }
}
