<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        if ($user->requiresSubscription()) {
            return redirect()->route('pricing');
        }

        return redirect()->intended($user->defaultAuthenticatedPath());
    }
}
