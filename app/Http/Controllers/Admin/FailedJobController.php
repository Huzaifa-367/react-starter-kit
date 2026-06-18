<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class FailedJobController extends Controller
{
    /**
     * Display a listing of failed jobs.
     */
    public function index()
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(15);

        if (request()->wantsJson()) {
            return response()->json($failedJobs);
        }

        return Inertia::render('admin/failed-jobs/index', [
            'failedJobs' => $failedJobs,
        ]);
    }

    /**
     * Retry a specific failed job.
     */
    public function retry(string $uuid)
    {
        try {
            // Check if job exists
            $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();
            if (!$job) {
                return request()->wantsJson()
                    ? response()->json(['error' => 'Job not found.'], 404)
                    : back()->withErrors(['error' => 'Job not found.']);
            }

            Artisan::call("queue:retry {$uuid}");

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job has been queued for retry.',
                ]);
            }

            return back()->with('status', 'Job has been queued for retry.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll()
    {
        try {
            Artisan::call('queue:retry all');

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'All failed jobs have been queued for retry.',
                ]);
            }

            return back()->with('status', 'All failed jobs have been queued for retry.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete a specific failed job.
     */
    public function destroy(string $uuid)
    {
        try {
            // Check if job exists
            $job = DB::table('failed_jobs')->where('uuid', $uuid)->first();
            if (!$job) {
                return request()->wantsJson()
                    ? response()->json(['error' => 'Job not found.'], 404)
                    : back()->withErrors(['error' => 'Job not found.']);
            }

            Artisan::call('queue:forget', [
                'id' => $uuid,
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Failed job deleted successfully.',
                ]);
            }

            return back()->with('status', 'Failed job deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Flush all failed jobs.
     */
    public function flush()
    {
        try {
            Artisan::call('queue:flush');

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'All failed jobs deleted successfully.',
                ]);
            }

            return back()->with('status', 'All failed jobs deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
