<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FcmNotificationController extends Controller
{
    /**
     * Register or update FCM token for the authenticated user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'device_type' => ['nullable', 'in:web,ios,android'],
            'device_name' => ['nullable', 'string', 'max:200'],
        ]);

        $token = FcmToken::updateOrCreate([
            'user_id' => auth()->id() ?: $request->user()->id,
            'token' => $request->token,
        ], [
            'device_type' => $request->device_type ?? 'web',
            'device_name' => $request->device_name,
            'last_used_at' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM token registered successfully.',
            'token' => $token,
        ]);
    }

    /**
     * Send a test push notification to a specific user.
     */
    public function send(Request $request)
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
        ]);

        $user = User::findOrFail($request->user_id);
        $fcmService = new FcmService();
        $success = $fcmService->sendToUser($user, $request->title, $request->body);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'failed',
                'message' => $success ? 'Notification sent successfully.' : 'Notification failed to send.',
            ]);
        }

        return back()->with(
            $success ? 'status' : 'error',
            $success ? 'Notification sent successfully.' : 'Notification failed to send.'
        );
    }

    /**
     * Broadcast a push notification to all active devices.
     */
    public function sendBroadcast(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
        ]);

        $tokens = FcmToken::active()->pluck('token')->toArray();
        $count = count($tokens);

        if ($count > 0) {
            $fcmService = new FcmService();
            $fcmService->send($tokens, $request->title, $request->body, ['type' => 'broadcast']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Broadcast sent successfully to {$count} active devices.",
            ]);
        }

        return back()->with('status', "Broadcast sent successfully to {$count} active devices.");
    }
}
