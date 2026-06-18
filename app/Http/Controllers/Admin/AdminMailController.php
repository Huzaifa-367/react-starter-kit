<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminMailController extends Controller
{
    /**
     * Send a direct diagnostic test email.
     */
    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $recipient = $request->email;
        $subject = $request->subject;
        $body = $request->body;
        $success = false;

        try {
            Mail::html($body, function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
            $success = true;
        } catch (\Exception $e) {
            Log::error("SMTP diagnostic test failed: " . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $success ? 'success' : 'failed',
                'message' => $success 
                    ? 'Test email sent successfully.' 
                    : 'Test email failed to send. Check SMTP settings.',
            ]);
        }

        return back()->with(
            $success ? 'status' : 'error',
            $success ? 'Test email sent successfully.' : 'Test email failed to send.'
        );
    }

    /**
     * Preview a template in the browser (returns raw HTML).
     */
    public function previewTemplate(string $templateKey)
    {
        $template = EmailTemplate::where('key', $templateKey)->first();

        if ($template) {
            $dummyData = [
                'user_name' => 'John Doe',
                'name' => 'John Doe',
                'otp_code' => '123456',
                'code' => '123456',
                'plan_name' => 'Pro Plan',
                'ends_in' => '3 days',
                'renews_on' => '2026-07-18',
                'price' => '$29.00',
                'grace_days_left' => '5',
                'reason' => 'Violation of terms',
                'invite_link' => url('/register?token=test'),
            ];

            $html = $template->body_html;
            foreach ($dummyData as $key => $val) {
                $html = str_replace(
                    ['{' . $key . '}', '{{' . $key . '}}', '{{ $' . $key . ' }}'],
                    $val,
                    $html
                );
            }

            return response($html)->header('Content-Type', 'text/html');
        }

        // Try dynamically rendering Mailable class
        $mailClass = "App\\Mail\\" . ucfirst(Str::camel($templateKey)) . "Mail";
        if (class_exists($mailClass)) {
            try {
                $user = auth()->user() ?: User::first();
                $mailable = new $mailClass($user, []);
                return $mailable->render();
            } catch (\Exception $e) {
                return response("Unable to render mailable '{$mailClass}': " . $e->getMessage(), 500);
            }
        }

        return response("Email template '{$templateKey}' not found.", 404);
    }
}
