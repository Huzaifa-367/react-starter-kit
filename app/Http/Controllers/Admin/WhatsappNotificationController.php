<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GreenApiService;
use Illuminate\Http\Request;

class WhatsappNotificationController extends Controller
{
    /**
     * Send a test WhatsApp message using Green API.
     */
    public function send(Request $request)
    {
        $request->validate([
            'phone_number' => ['nullable', 'string', 'max:30'],
            'user_id' => ['nullable', 'exists:users,id'],
            'message' => ['required', 'string'],
        ]);

        $phoneNumber = $request->phone_number;

        if ($request->filled('user_id')) {
            $user = User::find($request->user_id);
            if ($user && $user->phone_number) {
                $phoneNumber = $user->phone_number;
            }
        }

        if (!$phoneNumber) {
            return request()->wantsJson()
                ? response()->json(['error' => 'A recipient phone number is required.'], 422)
                : back()->withErrors(['phone_number' => 'A recipient phone number is required.']);
        }

        $greenApi = new GreenApiService();
        $success = $greenApi->sendMessage($phoneNumber, $request->message);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'failed',
                'message' => $success 
                    ? 'WhatsApp message sent successfully.' 
                    : 'WhatsApp message failed to send. Check Green API configuration.',
            ]);
        }

        return back()->with(
            $success ? 'status' : 'error',
            $success ? 'WhatsApp message sent successfully.' : 'WhatsApp message failed to send.'
        );
    }
}
