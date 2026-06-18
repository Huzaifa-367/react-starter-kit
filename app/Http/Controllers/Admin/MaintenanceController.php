<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    /**
     * Put the application into maintenance mode.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'secret' => ['nullable', 'string', 'min:4', 'alpha_num'],
            'redirect' => ['nullable', 'string'],
            'retry' => ['nullable', 'integer', 'min:1'],
        ]);

        $secret = $request->input('secret') ?: bin2hex(random_bytes(8));
        
        $params = [
            '--secret' => $secret,
        ];

        if ($request->filled('redirect')) {
            $params['--redirect'] = $request->redirect;
        }

        if ($request->filled('retry')) {
            $params['--retry'] = $request->retry;
        }

        try {
            Artisan::call('down', $params);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'System is now in maintenance mode.',
                    'secret' => $secret,
                    'bypass_url' => url('/' . $secret),
                ]);
            }

            return back()->with([
                'status' => 'System is now in maintenance mode.',
                'maintenance_secret' => $secret,
                'bypass_url' => url('/' . $secret),
            ]);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Take the application out of maintenance mode.
     */
    public function disable(Request $request)
    {
        try {
            Artisan::call('up');

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'System is now live.',
                ]);
            }

            return back()->with('status', 'System is now live.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
