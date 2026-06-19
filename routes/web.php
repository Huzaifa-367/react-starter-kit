<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMailController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BroadcastNotificationController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FailedJobController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\FcmNotificationController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\IpRuleController;
use App\Http\Controllers\Admin\LogViewerController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RateLimitController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SmsNotificationController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserSegmentController;
use App\Http\Controllers\Admin\WebhookLogController;
use App\Http\Controllers\Admin\WhatsappNotificationController;

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Settings\SessionController;

use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PlanController;
use App\Http\Controllers\Billing\StripeBillingController;

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\EmailBounceController;

use App\Models\Setting;

// ─── PUBLIC ──────────────────────────────────────────────────────────────
Route::inertia('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/pricing', [PlanController::class, 'pricing'])->name('pricing');
});

// Stripe & Email bounce webhooks
Route::post('/stripe/webhook', [StripeBillingController::class, 'handleWebhook'])->name('stripe.webhook');
Route::post('/webhooks/email/bounce', [EmailBounceController::class, 'handle'])->name('webhooks.email.bounce');

// Social OAuth
Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

// Magic Link
Route::get('/auth/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.form');
Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])->name('magic-link.send');
Route::get('/auth/magic-link/login', [MagicLinkController::class, 'login'])->name('magic-link.login');

// ─── AUTH (no subscription check yet) ───────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // OTP Verification
    Route::get('/verify/otp', [OtpController::class, 'show'])->name('verification.otp');
    Route::post('/verify/otp', [OtpController::class, 'verifyOtp'])->name('verification.otp.verify');
    Route::post('/verify/otp/resend', [OtpController::class, 'sendOtp'])
        ->middleware('throttle:5,1')->name('verification.otp.resend');

    // Plan selection / checkout (pre-subscription)
    Route::get('/billing/success', [StripeBillingController::class, 'checkoutSuccess'])->name('billing.success');
    Route::post('/billing/checkout', [StripeBillingController::class, 'checkoutSession'])
        ->middleware('throttle:10,1')->name('billing.checkout');
    Route::post('/billing/proration-preview', [StripeBillingController::class, 'previewProration'])->name('billing.proration-preview');

    // Send invitation (any auth user)
    Route::post('/invite', [InvitationController::class, 'userInvite'])
        ->middleware('throttle:10,60')->name('invite.send');

    // FCM token registration
    Route::post('/fcm/register', [FcmNotificationController::class, 'register'])->name('fcm.register');

    // In-app notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.count');

    // ToS acceptance
    Route::post('/terms/accept', fn(Request $r) => tap(
        auth()->user()->update(['terms_accepted_at' => now(), 'terms_version_accepted' => Setting::get('tos_version')]),
        fn() => redirect()->back()
    ))->name('terms.accept');
});

// ─── AUTH (role-aware dashboard redirect) ────────────────────────────────
Route::middleware(['auth', 'verified-tos'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->requiresSubscription()) {
            return redirect()->route('pricing');
        }

        return Inertia::render('dashboard');
    })->name('dashboard');
});

// ─── AUTH + SUBSCRIPTION REQUIRED ────────────────────────────────────────
Route::middleware(['auth', 'subscribed', 'verified-tos'])->group(function () {
    Route::get('/pricing/subscribed', [PlanController::class, 'pricing'])->name('pricing.subscribed');

    // Billing management
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [PlanController::class, 'dashboard'])->name('dashboard');
        Route::post('/portal', [StripeBillingController::class, 'billingPortal'])->name('portal');
        Route::post('/cancel', [StripeBillingController::class, 'cancelSubscription'])->name('cancel');
        Route::post('/resume', [StripeBillingController::class, 'resumeSubscription'])->name('resume');
        Route::post('/change-plan', [StripeBillingController::class, 'changePlan'])
            ->middleware('throttle:5,1')->name('change-plan');
        Route::get('/invoices/{invoiceId}/download', [InvoiceController::class, 'download'])->name('invoice.download');
    });

    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/phone', fn() => Inertia::render('profile/phone'))->name('phone.edit');
        Route::post('/phone', [ProfileController::class, 'updatePhone'])->name('phone.update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
        Route::post('/request-deletion', [ProfileController::class, 'requestDeletion'])->name('deletion.request');
        Route::get('/confirm-deletion', [ProfileController::class, 'confirmDeletion'])->name('deletion.confirm');
        Route::post('/export', [ProfileController::class, 'requestExport'])->name('export');
        Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');
        Route::delete('/sessions', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
        Route::get('/login-history', fn() => Inertia::render('profile/login-history', [
            'history' => auth()->user()->loginHistory()->latest('login_at')->paginate(20)
        ]))->name('login-history');
        Route::get('/referrals', [ProfileController::class, 'referrals'])->name('referrals');
    });
});

// ─── ADMIN (subscription not required) ───────────────────────────────────
Route::middleware(['auth', 'verified-tos', 'role:Admin|Super Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/trashed', [AdminUserController::class, 'trashed'])->name('trashed');
        Route::get('/{user}', [AdminUserController::class, 'show'])->name('show');
        Route::post('/{user}/suspend', [AdminUserController::class, 'suspend'])->name('suspend');
        Route::post('/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('unsuspend');
        Route::post('/{user}/assign-role', [AdminUserController::class, 'assignRole'])->name('assign-role');
        Route::post('/{user}/assign-plan', [AdminUserController::class, 'assignPlan'])->name('assign-plan');
        Route::post('/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('impersonate');
        Route::post('/{user}/notes', [AdminUserController::class, 'storeNote'])->name('notes.store');
        Route::delete('/{user}/notes/{note}', [AdminUserController::class, 'destroyNote'])->name('notes.destroy');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/restore', [AdminUserController::class, 'restore'])->name('restore');
        Route::post('/bulk', [AdminUserController::class, 'bulkAction'])->name('bulk');
        Route::post('/export', [AdminUserController::class, 'export'])->name('export');
    });
    Route::post('/impersonation/stop', [AdminUserController::class, 'stopImpersonation'])->name('impersonation.stop');

    // Roles & Permissions
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index'])->name('index');
        Route::post('/', [AdminRoleController::class, 'store'])->name('store');
        Route::put('/{role}', [AdminRoleController::class, 'update'])->name('update');
        Route::post('/{role}/sync-permissions', [AdminRoleController::class, 'syncPermissions'])->name('sync');
        Route::delete('/{role}', [AdminRoleController::class, 'destroy'])->name('destroy');
    });

    // Plans & Features
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index'])->name('index');
        Route::post('/', [AdminPlanController::class, 'store'])->name('store');
        Route::put('/{plan}', [AdminPlanController::class, 'update'])->name('update');
        Route::post('/{plan}/features', [AdminPlanController::class, 'syncFeatures'])->name('features.sync');
        Route::post('/{plan}/toggle', [AdminPlanController::class, 'toggleActive'])->name('toggle');
        Route::delete('/{plan}', [AdminPlanController::class, 'destroy'])->name('destroy');
    });

    // Invitations
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/', [InvitationController::class, 'index'])->name('index');
        Route::post('/', [InvitationController::class, 'store'])->name('store');
        Route::post('/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend');
        Route::delete('/{invitation}', [InvitationController::class, 'cancel'])->name('cancel');
    });

    // Coupons
    Route::apiResource('coupons', CouponController::class)->names('coupons');
    Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');

    // Segments
    Route::prefix('segments')->name('segments.')->group(function () {
        Route::get('/', [UserSegmentController::class, 'index'])->name('index');
        Route::post('/', [UserSegmentController::class, 'store'])->name('store');
        Route::put('/{segment}', [UserSegmentController::class, 'update'])->name('update');
        Route::post('/preview', [UserSegmentController::class, 'preview'])->name('preview');
        Route::delete('/{segment}', [UserSegmentController::class, 'destroy'])->name('destroy');
        Route::get('/{segment}/export', [UserSegmentController::class, 'export'])->name('export');
        Route::post('/{segment}/notify', [UserSegmentController::class, 'notify'])->name('notify');
    });

    // Broadcast Notifications
    Route::prefix('broadcasts')->name('broadcasts.')->group(function () {
        Route::get('/', [BroadcastNotificationController::class, 'index'])->name('index');
        Route::post('/', [BroadcastNotificationController::class, 'store'])->name('store');
        Route::post('/{broadcast}/send', [BroadcastNotificationController::class, 'send'])->name('send');
        Route::post('/preview', [BroadcastNotificationController::class, 'preview'])->name('preview');
        Route::delete('/{broadcast}', [BroadcastNotificationController::class, 'destroy'])->name('destroy');
    });

    // Email Templates
    Route::prefix('email-templates')->name('email-templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::get('/{template}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update');
        Route::get('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::post('/{template}/test', [EmailTemplateController::class, 'sendTest'])->name('test');
    });

    // Feature Flags
    Route::apiResource('feature-flags', FeatureFlagController::class)->names('feature-flags');

    // IP Rules
    Route::apiResource('ip-rules', IpRuleController::class)->names('ip-rules');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/sync-whatsapp', [SettingController::class, 'syncWhatsapp'])->name('settings.sync-whatsapp');

    // Maintenance Mode
    Route::post('/maintenance/enable', [MaintenanceController::class, 'enable'])->name('maintenance.enable');
    Route::post('/maintenance/disable', [MaintenanceController::class, 'disable'])->name('maintenance.disable');

    // System Operations
    Route::get('/system-health', [SystemHealthController::class, 'status'])->name('system-health');
    Route::post('/cache/flush', [CacheController::class, 'flush'])->name('cache.flush');
    Route::get('/failed-jobs', [FailedJobController::class, 'index'])->name('failed-jobs.index');
    Route::post('/failed-jobs/{uuid}/retry', [FailedJobController::class, 'retry'])->name('failed-jobs.retry');
    Route::post('/failed-jobs/retry-all', [FailedJobController::class, 'retryAll'])->name('failed-jobs.retry-all');
    Route::delete('/failed-jobs/{uuid}', [FailedJobController::class, 'destroy'])->name('failed-jobs.destroy');
    Route::delete('/failed-jobs', [FailedJobController::class, 'flush'])->name('failed-jobs.flush');
    Route::get('/rate-limits', [RateLimitController::class, 'index'])->name('rate-limits.index');
    Route::post('/rate-limits/unlock', [RateLimitController::class, 'unlock'])->name('rate-limits.unlock');

    // Webhook Logs
    Route::get('/webhook-logs', [WebhookLogController::class, 'index'])->name('webhook-logs.index');
    Route::get('/webhook-logs/{log}', [WebhookLogController::class, 'show'])->name('webhook-logs.show');
    Route::post('/webhook-logs/{log}/reprocess', [WebhookLogController::class, 'reprocess'])->name('webhook-logs.reprocess');

    // Logs
    Route::get('/logs', [LogViewerController::class, 'index'])->name('logs.index');
    Route::get('/logs/{filename}', [LogViewerController::class, 'show'])->name('logs.show');
    Route::get('/logs/{filename}/download', [LogViewerController::class, 'download'])->name('logs.download');
    Route::delete('/logs/{filename}', [LogViewerController::class, 'clear'])->middleware('role:Super Admin')->name('logs.clear');

    // Activity Log
    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');
    Route::get('/activity/export', [ActivityLogController::class, 'export'])->name('activity.export');

    // Diagnostics
    Route::prefix('diagnostics')->name('diagnostics.')->group(function () {
        Route::post('/email', [AdminMailController::class, 'sendTestEmail'])->name('email');
        Route::get('/email/preview/{template}', [AdminMailController::class, 'previewTemplate'])->name('email.preview');
        Route::post('/fcm', [FcmNotificationController::class, 'send'])->name('fcm');
        Route::post('/fcm/broadcast', [FcmNotificationController::class, 'sendBroadcast'])->name('fcm.broadcast');
        Route::post('/whatsapp', [WhatsappNotificationController::class, 'send'])->name('whatsapp');
        Route::post('/sms', [SmsNotificationController::class, 'send'])->name('sms');
    });
});

require __DIR__ . '/settings.php';
