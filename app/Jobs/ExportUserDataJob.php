<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->queue = 'low';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;

        // Gather GDPR data
        $exportData = [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'created_at' => $user->created_at?->toIso8601String(),
                'updated_at' => $user->updated_at?->toIso8601String(),
            ],
            'subscription' => $user->getActiveSubscription() ? [
                'id' => $user->getActiveSubscription()->id,
                'plan_name' => $user->getActiveSubscription()->plan?->name,
                'status' => $user->getActiveSubscription()->status,
                'trial_ends_at' => $user->getActiveSubscription()->trial_ends_at?->toIso8601String(),
                'ends_at' => $user->getActiveSubscription()->ends_at?->toIso8601String(),
            ] : null,
            'login_history' => $user->loginHistory()->latest('login_at')->take(100)->get()->map(fn($log) => [
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'login_at' => $log->login_at?->toIso8601String(),
                'status' => $log->status,
            ])->toArray(),
            'activity_logs' => ActivityLog::where('user_id', $user->id)->latest()->take(100)->get()->map(fn($log) => [
                'event' => $log->event,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->toArray(),
            'credits' => $user->credits()->get()->map(fn($credit) => [
                'amount' => $credit->amount,
                'type' => $credit->type,
                'description' => $credit->description,
                'created_at' => $credit->created_at?->toIso8601String(),
            ])->toArray(),
        ];

        // Store file securely in public storage with dynamic tokens
        $token = Str::random(40);
        $fileName = "exports/gdpr_{$user->id}_{$token}.json";
        
        Storage::disk('public')->put($fileName, json_encode($exportData, JSON_PRETTY_PRINT));
        $downloadUrl = url(Storage::url($fileName));

        // Send download link via email
        $recipient = $user->email;
        $subject = 'Your Account GDPR Data Export';
        $body = "
            <p>Hello {$user->name},</p>
            <p>Your personal data export request has been processed.</p>
            <p>Click the link below to download the JSON data file:</p>
            <p><a href='{$downloadUrl}' style='padding: 10px 15px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px;'>Download GDPR JSON Export</a></p>
            <p>This link is valid for 24 hours.</p>
            <p>Thank you!</p>
        ";

        Mail::html($body, function ($message) use ($recipient, $subject) {
            $message->to($recipient)->subject($subject);
        });
    }
}
