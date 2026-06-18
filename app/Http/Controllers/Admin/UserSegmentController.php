<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserSegment;
use App\Services\SegmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserSegmentController extends Controller
{
    protected SegmentService $segmentService;

    public function __construct(SegmentService $segmentService)
    {
        $this->segmentService = $segmentService;
    }

    /**
     * Display a listing of user segments.
     */
    public function index(): Response
    {
        // Periodically refresh segment counts on load
        $this->segmentService->refreshAllCounts();

        $segments = UserSegment::latest()->get();

        return Inertia::render('admin/segments/index', [
            'segments' => $segments,
        ]);
    }

    /**
     * Store a newly created segment.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'filters' => ['required', 'array'],
        ]);

        $segment = UserSegment::create([
            'name' => $request->name,
            'description' => $request->description,
            'filters' => $request->filters,
        ]);

        // Evaluate immediately
        $this->segmentService->evaluate($segment);

        return back()->with('status', 'Segment created and evaluated.');
    }

    /**
     * Update the specified segment.
     */
    public function update(Request $request, UserSegment $segment): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'filters' => ['required', 'array'],
        ]);

        $segment->update([
            'name' => $request->name,
            'description' => $request->description,
            'filters' => $request->filters,
        ]);

        $this->segmentService->evaluate($segment);

        return back()->with('status', 'Segment updated and re-evaluated.');
    }

    /**
     * Preview count of segment matches without saving.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'filters' => ['required', 'array'],
        ]);

        $query = $this->segmentService->buildQuery($request->filters);
        $count = $query->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Export segment users as a CSV file.
     */
    public function export(UserSegment $segment): StreamedResponse
    {
        $query = $this->segmentService->buildQuery($segment->filters ?? []);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="segment_users_' . urlencode($segment->name) . '_' . time() . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone Number', 'Created At']);

            $query->chunk(500, function ($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->phone_number ?? 'N/A',
                        $user->created_at->toIso8601String(),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Create a draft broadcast targeted at this user segment.
     */
    public function notify(UserSegment $segment): RedirectResponse
    {
        return redirect()->route('admin.broadcasts.index', [
            'target_type' => 'segment',
            'target_id' => $segment->id,
        ]);
    }

    /**
     * Remove the specified segment.
     */
    public function destroy(UserSegment $segment): RedirectResponse
    {
        $segment->delete();

        return back()->with('status', 'Segment deleted successfully.');
    }
}
