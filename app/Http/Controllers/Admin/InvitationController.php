<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use OffloadProject\InviteOnly\Models\Invitation;
use OffloadProject\InviteOnly\Enums\InvitationStatus;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations.
     */
    public function index(Request $request): Response
    {
        $invitations = Invitation::with('inviter')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/invitations/index', [
            'invitations' => $invitations,
        ]);
    }

    /**
     * Store a newly created invitation (admin route).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Invitation::generateToken(),
            'status' => InvitationStatus::Pending,
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(7),
            'message' => $request->message,
            'metadata' => ['phone_number' => $request->phone_number],
        ]);

        $inviteLink = route('register') . '?invitation_token=' . $invitation->token;

        $dummyUser = new \stdClass();
        $dummyUser->id = 0;
        $dummyUser->name = 'Guest';
        $dummyUser->email = $request->email;
        $dummyUser->phone_number = $request->phone_number;

        try {
            \App\Services\NotificationDispatcher::dispatch($dummyUser, 'invitation_sent', [
                'invite_link' => $inviteLink,
            ]);
            $invitation->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to send admin invitation notification: " . $e->getMessage());
        }

        return back()->with('status', 'Invitation sent successfully.');
    }

    /**
     * Resend an invitation.
     */
    public function resend(Request $request, int $id): RedirectResponse
    {
        $invitation = Invitation::findOrFail($id);

        $invitation->update([
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(7),
            'status' => InvitationStatus::Pending,
        ]);

        $inviteLink = route('register') . '?invitation_token=' . $invitation->token;

        $dummyUser = new \stdClass();
        $dummyUser->id = 0;
        $dummyUser->name = 'Guest';
        $dummyUser->email = $invitation->email;
        
        $metadata = $invitation->metadata ?? [];
        $dummyUser->phone_number = $metadata['phone_number'] ?? null;

        try {
            \App\Services\NotificationDispatcher::dispatch($dummyUser, 'invitation_sent', [
                'invite_link' => $inviteLink,
            ]);
            $invitation->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to resend invitation notification: " . $e->getMessage());
        }

        return back()->with('status', 'Invitation resent successfully.');
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(int $id): RedirectResponse
    {
        $invitation = Invitation::findOrFail($id);
        $invitation->markAsCancelled();

        return back()->with('status', 'Invitation cancelled.');
    }

    /**
     * Public user-triggered invitation creation.
     */
    public function userInvite(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $invitation = Invitation::create([
            'email' => $request->email,
            'token' => Invitation::generateToken(),
            'status' => InvitationStatus::Pending,
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(7),
            'message' => $request->message,
            'metadata' => ['phone_number' => $request->phone_number],
        ]);

        $inviteLink = route('register') . '?invitation_token=' . $invitation->token;

        $dummyUser = new \stdClass();
        $dummyUser->id = 0;
        $dummyUser->name = 'Guest';
        $dummyUser->email = $request->email;
        $dummyUser->phone_number = $request->phone_number;

        try {
            \App\Services\NotificationDispatcher::dispatch($dummyUser, 'invitation_sent', [
                'invite_link' => $inviteLink,
            ]);
            $invitation->markAsSent();
        } catch (\Exception $e) {
            Log::error("Failed to send user invitation: " . $e->getMessage());
        }

        return back()->with('status', 'Invitation sent successfully.');
    }
}
