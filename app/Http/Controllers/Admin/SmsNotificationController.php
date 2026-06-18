<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsNotificationController extends Controller
{
    /**
     * Send a test SMS message using Twilio.
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

        $twilio = new TwilioService();
        $success = false;

        try {
            $twilio->sendSms($phoneNumber, $request->message);
            $success = true;
        } catch (\Exception $e) {
            Log::error("Twilio SMS diagnostic failed: " . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'failed',
                'message' => $success 
                    ? 'SMS sent successfully.' 
                    : 'SMS failed to send. Check Twilio configuration.',
            ]);
        }

        return back()->with(
            $success ? 'status' : 'error',
            $success ? 'SMS sent successfully.' : 'SMS failed to send.'
        );
    }
}
