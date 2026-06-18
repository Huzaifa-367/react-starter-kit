<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of email templates.
     */
    public function index()
    {
        $templates = EmailTemplate::latest()->paginate(15);

        if (request()->wantsJson()) {
            return response()->json($templates);
        }

        return Inertia::render('admin/email-templates/index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for editing the specified email template.
     */
    public function edit(EmailTemplate $template): Response
    {
        return Inertia::render('admin/email-templates/edit', [
            'template' => $template,
        ]);
    }

    /**
     * Update the specified email template in storage.
     */
    public function update(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $template->update($request->only(['subject', 'body_html', 'body_text', 'is_active']));

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email template updated successfully.',
                'template' => $template,
            ]);
        }

        return redirect()->route('admin.email-templates.index')->with('status', 'Email template updated successfully.');
    }

    /**
     * Preview the email template with dummy variables populated.
     */
    public function preview(EmailTemplate $template): JsonResponse
    {
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

        return response()->json([
            'subject' => $template->subject,
            'html' => $html,
        ]);
    }

    /**
     * Send a test email of this template to a given recipient.
     */
    public function sendTest(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $recipient = $request->email;

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

        $subject = '[TEST] ' . $template->subject;

        try {
            Mail::html($html, function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Mail sending failed: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['email' => 'Mail sending failed: ' . $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent successfully to ' . $recipient,
            ]);
        }

        return back()->with('status', 'Test email sent successfully to ' . $recipient);
    }
}
