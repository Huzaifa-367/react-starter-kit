<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNote;
use App\Models\Plan;
use App\Services\AuditLogger;
use App\Services\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $query = User::query()->with(['roles', 'activeSubscription.plan']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereHas('activeSubscription', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if ($request->filled('plan')) {
            $planId = $request->input('plan');
            $query->whereHas('activeSubscription', function ($q) use ($planId) {
                $q->where('plan_id', $planId);
            });
        }

        if ($request->filled('suspended')) {
            $query->where('is_suspended', $request->boolean('suspended'));
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::all(['id', 'name']);
        $plans = Plan::all(['id', 'name']);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => $roles,
            'plans' => $plans,
            'filters' => $request->only(['search', 'role', 'status', 'plan', 'suspended']),
        ]);
    }

    /**
     * Display the specified user's profile and history.
     */
    public function show(User $user): Response
    {
        $user->load(['roles']);
        
        $subscriptions = $user->subscriptions()->with('plan')->latest()->get();
        
        $activityLogs = \App\Models\ActivityLog::where('user_id', $user->id)
            ->orWhere(function ($q) use ($user) {
                $q->where('subject_type', User::class)
                  ->where('subject_id', $user->id);
            })
            ->latest()
            ->take(50)
            ->get();

        $notificationLogs = $user->notificationLogs()->latest()->take(50)->get();
        $fcmTokens = $user->fcmTokens()->latest()->get();
        $userNotes = $user->notes()->with('admin')->latest()->get();
        $credits = $user->credits()->latest()->get();
        $loginHistory = $user->loginHistory()->latest('login_at')->take(50)->get();

        return Inertia::render('admin/users/show', [
            'user' => $user,
            'subscriptions' => $subscriptions,
            'activityLogs' => $activityLogs,
            'notificationLogs' => $notificationLogs,
            'fcmTokens' => $fcmTokens,
            'userNotes' => $userNotes,
            'credits' => $credits,
            'loginHistory' => $loginHistory,
            'allRoles' => Role::all(['id', 'name']),
        ]);
    }


    /**
     * Suspend a user's account.
     */
    public function suspend(User $user, Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspended_reason' => $request->reason,
        ]);

        // Terminate user sessions immediately
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        // Send suspension notification
        try {
            \App\Services\NotificationDispatcher::dispatch($user, 'account_suspended');
        } catch (\Exception $e) {
            Log::error("Suspension notification dispatch failed: " . $e->getMessage());
        }

        AuditLogger::log('user.suspended', $user, [], ['reason' => $request->reason]);

        return back()->with('status', 'User account suspended.');
    }

    /**
     * Unsuspend a user's account.
     */
    public function unsuspend(User $user): RedirectResponse
    {
        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        AuditLogger::log('user.unsuspended', $user);

        return back()->with('status', 'User account unsuspended.');
    }

    /**
     * Assign Spatie roles to a user.
     */
    public function assignRole(User $user, Request $request): RedirectResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
        ]);

        $user->syncRoles($request->roles);

        // Reset Spatie cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        AuditLogger::log('user.roles.assigned', $user, [], ['roles' => $request->roles]);

        return back()->with('status', 'User roles updated.');
    }

    /**
     * Manually assign a plan to a user (Admin bypass, no Stripe).
     */
    public function assignPlan(User $user, Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        
        $subManager = new SubscriptionManager();
        $subManager->subscribeTo($user, $plan);

        AuditLogger::log('user.plan.assigned', $user, [], ['plan_id' => $plan->id]);

        return back()->with('status', 'User plan assigned successfully.');
    }

    /**
     * Soft delete a user account.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Cancel active subscriptions immediately
        $sub = $user->subscriptions()->where('status', 'active')->first();
        if ($sub) {
            try {
                $subManager = new SubscriptionManager();
                $subManager->cancelImmediately($sub);
            } catch (\Exception $e) {
                Log::error("Failed to cancel Stripe subscription during admin deletion: " . $e->getMessage());
            }
        }

        // Deactivate FCM tokens
        $user->fcmTokens()->update(['is_active' => false]);

        AuditLogger::log('user.deleted', $user);

        $user->delete();

        return back()->with('status', 'User soft deleted successfully.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        AuditLogger::log('user.restored', $user);

        return back()->with('status', 'User restored successfully.');
    }

    /**
     * Start impersonating a user.
     */
    public function impersonate(User $user): RedirectResponse
    {
        $admin = Auth::user();

        if (!$admin || (!$admin->hasRole('Admin') && !$admin->hasRole('Super Admin'))) {
            abort(403, 'Only administrators can impersonate.');
        }

        // Prevent unauthorized impersonation
        if ($user->hasRole('Super Admin') || ($user->hasRole('Admin') && !$admin->hasRole('Super Admin'))) {
            abort(403, 'You cannot impersonate this user.');
        }

        session(['impersonating_admin_id' => $admin->id]);
        session(['impersonation_started_at' => now()->toIso8601String()]);

        Auth::login($user);

        AuditLogger::log('impersonation.started', $user);

        return redirect()->route('dashboard')->with('status', "Now impersonating {$user->name}.");
    }

    /**
     * Stop impersonating and return to admin session.
     */
    public function stopImpersonation(): RedirectResponse
    {
        $adminId = session('impersonating_admin_id');
        if (!$adminId) {
            return redirect()->route('dashboard');
        }

        $impersonatedUser = Auth::user();

        session()->forget('impersonating_admin_id');
        session()->forget('impersonation_started_at');

        Auth::loginUsingId($adminId);

        AuditLogger::log('impersonation.ended', $impersonatedUser);

        return redirect()->route('admin.users.index')->with('status', 'Impersonation stopped.');
    }

    /**
     * Add a note to the user profile.
     */
    public function storeNote(User $user, Request $request): RedirectResponse
    {
        $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $user->notes()->create([
            'admin_id' => Auth::id(),
            'content' => $request->content,
        ]);

        return back()->with('status', 'Note added.');
    }

    /**
     * Remove a note from user profile.
     */
    public function destroyNote(UserNote $note): RedirectResponse
    {
        if ($note->admin_id !== Auth::id() && !Auth::user()->hasRole('Super Admin')) {
            abort(403, 'Unauthorized.');
        }

        $note->delete();

        return back()->with('status', 'Note deleted.');
    }

    /**
     * Execute bulk actions on selected users.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'action' => ['required', 'string', 'in:suspend,unsuspend,assign_role,export'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $userIds = $request->user_ids;
        $action = $request->action;

        if ($action === 'suspend') {
            User::whereIn('id', $userIds)->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspended_reason' => $request->reason ?? 'Bulk suspended',
            ]);
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }
        } elseif ($action === 'unsuspend') {
            User::whereIn('id', $userIds)->update([
                'is_suspended' => false,
                'suspended_at' => null,
                'suspended_reason' => null,
            ]);
        } elseif ($action === 'assign_role') {
            $roleName = $request->role;
            foreach ($userIds as $id) {
                $u = User::find($id);
                if ($u) {
                    $u->syncRoles([$roleName]);
                }
            }
        } elseif ($action === 'export') {
            if (class_exists(\App\Jobs\BulkUserExportJob::class)) {
                \App\Jobs\BulkUserExportJob::dispatch($userIds, Auth::id());
            }
            return back()->with('status', 'Bulk user export has been queued.');
        }

        return back()->with('status', 'Bulk action completed.');
    }

    /**
     * Export all users database.
     */
    public function export(): RedirectResponse
    {
        $userIds = User::pluck('id')->toArray();
        if (class_exists(\App\Jobs\BulkUserExportJob::class)) {
            \App\Jobs\BulkUserExportJob::dispatch($userIds, Auth::id());
        }
        return back()->with('status', 'User export has been queued.');
    }

    /**
     * Display a listing of soft-deleted users.
     */
    public function trashed(): Response
    {
        $users = User::onlyTrashed()->with(['roles', 'activeSubscription.plan'])->paginate(20);
        return Inertia::render('admin/users/trashed', [
            'users' => $users,
        ]);
    }
}
