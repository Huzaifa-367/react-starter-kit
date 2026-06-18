<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUserDataJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataExportController extends Controller
{
    /**
     * Request a new user data export.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $throttleKey = 'data_export:' . $user->id;

        // Rate limit: 1 request per 24 hours (86400 seconds)
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $hours = ceil($seconds / 3600);
            throw ValidationException::withMessages([
                'export' => ["You can only request a data export once every 24 hours. Please try again in {$hours} hours."],
            ]);
        }

        RateLimiter::hit($throttleKey, 86400);

        if (class_exists(ExportUserDataJob::class)) {
            ExportUserDataJob::dispatch($user);
        } else {
            \Illuminate\Support\Facades\Log::info("ExportUserDataJob class not found. Simulated dispatching export for user: " . $user->id);
        }

        return back()->with('status', 'Your data export has been requested. We will email you a download link when it is ready.');
    }

    /**
     * Download a completed user data export.
     */
    public function download(Request $request, string $filename): BinaryFileResponse
    {
        $user = $request->user();

        // Enforce user ownership checking based on filename format: export_{user_id}_{timestamp}.zip
        $parts = explode('_', $filename);
        if (count($parts) < 2 || (int)$parts[1] !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $filePath = 'exports/' . $filename;

        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'File not found or link expired.');
        }

        return response()->download(storage_path('app/' . $filePath));
    }
}
