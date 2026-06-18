<?php

namespace App\Services;

use App\Models\User;
use App\Models\Setting;
use App\Models\FcmToken;
use App\Models\NotificationLog;
use DevKandil\NotiFire\Facades\Fcm;

class FcmService
{
    /**
     * Send FCM push notification to one or multiple tokens.
     */
    public function send(string|array $tokens, string $title, string $body, array $data = []): bool
    {
        // Check database configurations first
        $projectId = Setting::get('firebase_project_id');
        $privateKey = Setting::get('firebase_private_key');
        $clientEmail = Setting::get('firebase_client_email');

        if (empty($projectId) || empty($privateKey) || empty($clientEmail)) {
            return false;
        }

        // Write firebase.json service account credentials file dynamically
        $credentials = [
            'type' => 'service_account',
            'project_id' => $projectId,
            'private_key_id' => Setting::get('firebase_private_key_id', 'sa-key-id'),
            'private_key' => $privateKey,
            'client_email' => $clientEmail,
            'client_id' => '1234567890',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/' . urlencode($clientEmail),
        ];

        $credentialsPath = storage_path('firebase.json');
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0755, true);
        }
        file_put_contents($credentialsPath, json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Dynamically set config values for NotiFire package
        config([
            'fcm.project_id' => $projectId,
            'fcm.credentials_path' => $credentialsPath,
        ]);

        $tokensList = is_array($tokens) ? $tokens : [$tokens];
        $allSuccess = true;

        $originalThrow = config('fcm.throw_exceptions');
        config(['fcm.throw_exceptions' => true]);

        foreach ($tokensList as $token) {
            if (empty($token)) {
                continue;
            }

            $userToken = FcmToken::where('token', $token)->first();
            $userId = $userToken ? $userToken->user_id : null;

            try {
                $success = Fcm::withTitle($title)
                    ->withBody($body)
                    ->withAdditionalData($data)
                    ->sendNotification($token);

                if ($success) {
                    if ($userToken) {
                        $userToken->update(['last_used_at' => now()]);
                    }

                    NotificationLog::create([
                        'user_id' => $userId,
                        'channel' => 'fcm',
                        'type' => $data['type'] ?? 'custom',
                        'recipient' => $token,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                } else {
                    $allSuccess = false;
                    NotificationLog::create([
                        'user_id' => $userId,
                        'channel' => 'fcm',
                        'type' => $data['type'] ?? 'custom',
                        'recipient' => $token,
                        'status' => 'failed',
                        'error_message' => 'FCM send operation returned false.',
                        'sent_at' => null,
                    ]);
                }

            } catch (\Exception $e) {
                $allSuccess = false;
                $errorMessage = $e->getMessage();

                // Check if exception indicates unregistered or invalid token
                $isInvalidToken = false;
                if ($e instanceof \DevKandil\NotiFire\Exceptions\FcmRequestException) {
                    $respData = $e->getResponseData();
                    $respBody = $respData['response'] ?? '';
                    $isInvalidToken = str_contains(strtolower($respBody), 'unregistered') 
                        || str_contains(strtolower($respBody), 'invalid_argument')
                        || str_contains(strtolower($respBody), 'invalid-token-format')
                        || str_contains(strtolower($respBody), 'invalid_token');
                }

                if (str_contains(strtolower($errorMessage), 'unregistered') 
                    || str_contains(strtolower($errorMessage), 'invalid') 
                    || $isInvalidToken) {
                    
                    $this->deactivateToken($token);
                }

                NotificationLog::create([
                    'user_id' => $userId,
                    'channel' => 'fcm',
                    'type' => $data['type'] ?? 'custom',
                    'recipient' => $token,
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                    'sent_at' => null,
                ]);
            }
        }

        config(['fcm.throw_exceptions' => $originalThrow]);

        return $allSuccess;
    }

    /**
     * Send notification to all active tokens of a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $tokens = FcmToken::where('user_id', $user->id)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return false;
        }

        return $this->send($tokens, $title, $body, $data);
    }

    /**
     * Register a new device token for a user.
     */
    public function registerToken(User $user, string $token, string $deviceType, ?string $deviceName): FcmToken
    {
        // Deactivate old active tokens for same device_type
        FcmToken::where('user_id', $user->id)
            ->where('device_type', $deviceType)
            ->update(['is_active' => false]);

        // Create or update the token, setting it to active
        return FcmToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Deactivate a specific token.
     */
    public function deactivateToken(string $token): void
    {
        FcmToken::where('token', $token)->update(['is_active' => false]);
    }
}
