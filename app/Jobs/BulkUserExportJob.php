<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkUserExportJob implements ShouldQueue
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

    protected User $adminUser;

    /**
     * @var array<int>|null
     */
    protected ?array $userIds;

    /**
     * Create a new job instance.
     *
     * @param array<int>|null $userIds
     */
    public function __construct(User $adminUser, ?array $userIds = null)
    {
        $this->adminUser = $adminUser;
        $this->userIds = $userIds;
        $this->queue = 'low';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $admin = $this->adminUser;
        $token = Str::random(40);
        $fileName = "exports/bulk_users_{$admin->id}_{$token}.csv";

        $tempFile = tempnam(sys_get_temp_dir(), 'user_export');
        if ($tempFile === false) {
            throw new \Exception("Failed to generate temporary file name.");
        }
        $file = fopen($tempFile, 'w');
        if ($file === false) {
            throw new \Exception("Failed to open temporary file for writing.");
        }

        // CSV headers
        fputcsv($file, ['ID', 'Name', 'Email', 'Phone Number', 'Role(s)', 'Active Plan', 'Subscription Status', 'Suspended', 'Created At']);

        $query = User::with(['roles', 'activeSubscription.plan']);
        if (!empty($this->userIds)) {
            $query->whereIn('id', $this->userIds);
        }

        $query->chunk(500, function ($users) use ($file) {
            foreach ($users as $user) {
                $sub = $user->activeSubscription;
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone_number ?? 'N/A',
                    $user->roles->pluck('name')->implode(', ') ?: 'User',
                    $sub ? ($sub->plan?->name ?? 'Free Starter') : 'None',
                    $sub ? $sub->status : 'inactive',
                    $user->is_suspended ? 'Yes' : 'No',
                    $user->created_at?->toIso8601String(),
                ]);
            }
        });

        fclose($file);

        // Upload to public disk
        $readStream = fopen($tempFile, 'r');
        if ($readStream === false) {
            throw new \Exception("Failed to open temporary file for reading.");
        }
        Storage::disk('public')->put($fileName, $readStream);
        fclose($readStream);
        unlink($tempFile);

        $downloadUrl = url(Storage::url($fileName));

        // Send download link via email
        $recipient = $admin->email;
        $subject = 'Bulk User Database Export Completed';
        $body = "
            <p>Hello {$admin->name},</p>
            <p>Your request to export the system user database has completed.</p>
            <p>Click the link below to download the CSV export:</p>
            <p><a href='{$downloadUrl}' style='padding: 10px 15px; background: #28a745; color: #fff; text-decoration: none; border-radius: 4px;'>Download Users CSV</a></p>
            <p>This link is valid for 24 hours.</p>
            <p>Thank you!</p>
        ";

        Mail::html($body, function ($message) use ($recipient, $subject) {
            $message->to($recipient)->subject($subject);
        });
    }
}
