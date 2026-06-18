<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs with filters.
     */
    public function index(Request $request): Response
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $logs = $query->latest('id')->paginate(50)->withQueryString();

        return Inertia::render('admin/activity-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['user_id', 'event', 'subject_type', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Export activity logs as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="activity_logs_' . time() . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'User', 'Event', 'Subject Type', 'Subject ID', 'IP Address', 'User Agent', 'Date']);

            $query->chunk(500, function ($logs) use ($file) {
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->user?->email ?? 'System',
                        $log->event,
                        $log->subject_type ?? 'N/A',
                        $log->subject_id ?? 'N/A',
                        $log->ip_address ?? 'N/A',
                        $log->user_agent ?? 'N/A',
                        $log->created_at->toIso8601String(),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
