<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadcastNotification;
use App\Models\User;
use App\Models\Plan;
use App\Models\UserSegment;
use App\Services\SegmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class BroadcastNotificationController extends Controller
{
    /**
     * Display a listing of broadcast notifications.
     */
    public function index(): Response
    {
        $broadcasts = BroadcastNotification::with('admin')
            ->latest()
            ->paginate(20);

        $plans = Plan::all(['id', 'name']);
        $roles = Role::all(['id', 'name']);
        $segments = UserSegment::all(['id', 'name']);

        return Inertia::render('admin/broadcasts/index', [
            'broadcasts' => $broadcasts,
            'plans' => $plans,
            'roles' => $roles,
            'segments' => $segments,
        ]);
    }

    /**
     * Store a newly created broadcast notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:email,fcm,whatsapp,sms'],
            'target_type' => ['required', 'in:all,plan,role,segment'],
            'target_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $totalRecipients = $this->resolveRecipientQuery($request->target_type, $request->target_id)->count();
        $status = $request->filled('scheduled_at') ? 'scheduled' : 'draft';

        $broadcast = BroadcastNotification::create([
            'admin_id' => Auth::id(),
            'title' => $request->title,
            'body' => $request->body,
            'channels' => $request->channels,
            'target_type' => $request->target_type,
            'target_id' => $request->target_id,
            'status' => $status,
            'scheduled_at' => $request->scheduled_at,
            'total_recipients' => $totalRecipients,
        ]);

        // Dispatch immediately if not scheduled for future
        if (!$request->filled('scheduled_at')) {
            $broadcast->update(['status' => 'scheduled']);
            
            if (class_exists(\App\Jobs\SendBroadcastNotificationJob::class)) {
                \App\Jobs\SendBroadcastNotificationJob::dispatch($broadcast);
            }
        }

        return back()->with('status', 'Broadcast saved successfully.');
    }

    /**
     * Dispatch an existing draft or scheduled broadcast.
     */
    public function send(BroadcastNotification $broadcast): RedirectResponse
    {
        if (!in_array($broadcast->status, ['draft', 'scheduled'])) {
            return back()->withErrors(['error' => 'Only draft or scheduled broadcasts can be sent.']);
        }

        $broadcast->update(['status' => 'scheduled']);

        if (class_exists(\App\Jobs\SendBroadcastNotificationJob::class)) {
            \App\Jobs\SendBroadcastNotificationJob::dispatch($broadcast);
        }

        return back()->with('status', 'Broadcast has been queued for delivery.');
    }

    /**
     * Get estimated count of recipients for a given target.
     */
    public function preview(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'target_type' => ['required', 'in:all,plan,role,segment'],
            'target_id' => ['nullable', 'integer'],
        ]);

        $count = $this->resolveRecipientQuery($request->target_type, $request->target_id)->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Delete a draft or scheduled broadcast notification.
     */
    public function destroy(BroadcastNotification $broadcast): RedirectResponse
    {
        if (!in_array($broadcast->status, ['draft', 'scheduled'])) {
            return back()->withErrors(['error' => 'Active or sent broadcasts cannot be deleted.']);
        }

        $broadcast->delete();

        return back()->with('status', 'Broadcast deleted successfully.');
    }

    /**
     * Helper to resolve the recipient query based on target criteria.
     */
    private function resolveRecipientQuery(string $targetType, ?int $targetId): Builder
    {
        $query = User::query();

        if ($targetType === 'plan') {
            $query->whereHas('activeSubscription', function ($q) use ($targetId) {
                $q->where('plan_id', $targetId);
            });
        } elseif ($targetType === 'role') {
            $role = Role::find($targetId);
            if ($role) {
                $query->role($role->name);
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($targetType === 'segment') {
            $segment = UserSegment::find($targetId);
            if ($segment) {
                $segmentService = new SegmentService();
                $query = $segmentService->buildQuery($segment->filters ?? []);
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query;
    }
}
