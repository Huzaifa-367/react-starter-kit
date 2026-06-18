<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class WebhookLogController extends Controller
{
    /**
     * Display a listing of webhook logs.
     */
    public function index()
    {
        $logs = WebhookLog::latest()->paginate(20);

        if (request()->wantsJson()) {
            return response()->json($logs);
        }

        return Inertia::render('admin/webhook-logs/index', [
            'logs' => $logs,
        ]);
    }

    /**
     * Display the specified webhook log.
     */
    public function show(WebhookLog $log)
    {
        if (request()->wantsJson()) {
            return response()->json($log);
        }

        return Inertia::render('admin/webhook-logs/show', [
            'log' => $log,
        ]);
    }

    /**
     * Reprocess a specific webhook log.
     */
    public function reprocess(WebhookLog $log)
    {
        $payload = is_array($log->payload) ? $log->payload : json_decode($log->payload, true);
        if (!$payload) {
            return request()->wantsJson()
                ? response()->json(['error' => 'Invalid payload JSON.'], 422)
                : back()->withErrors(['error' => 'Invalid payload JSON.']);
        }

        $subManager = new SubscriptionManager();

        try {
            $eventType = $log->event_type;
            $dataObject = $payload['data']['object'] ?? null;

            if (!$dataObject) {
                throw new \Exception("Missing data object in event payload.");
            }

            // Convert standard object structure
            $object = json_decode(json_encode($dataObject));

            switch ($eventType) {
                case 'checkout.session.completed':
                    $userId = $object->metadata->user_id ?? null;
                    $planId = $object->metadata->plan_id ?? null;

                    if ($userId && $planId) {
                        $user = User::find($userId);
                        $plan = Plan::find($planId);
                        if ($user && $plan) {
                            $exists = Subscription::where('stripe_id', $object->subscription)->exists();
                            if (!$exists) {
                                $subManager->subscribeTo($user, $plan, $object->subscription);
                            }
                        }
                    }
                    break;

                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    // syncFromStripe expects Stripe object or stdClass
                    $subManager->syncFromStripe($object);
                    break;
                
                default:
                    // Log unsupported event but don't fail
                    break;
            }

            $log->update([
                'processed' => true,
                'error' => null,
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Webhook log reprocessed successfully.',
                    'log' => $log,
                ]);
            }

            return back()->with('status', 'Webhook log reprocessed successfully.');

        } catch (\Exception $e) {
            $log->update([
                'processed' => false,
                'error' => $e->getMessage(),
            ]);

            if (request()->wantsJson()) {
                return response()->json(['error' => 'Reprocessing failed: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Reprocessing failed: ' . $e->getMessage()]);
        }
    }
}
