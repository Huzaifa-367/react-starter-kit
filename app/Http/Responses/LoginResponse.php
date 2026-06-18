<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        if ($user->requiresSubscription()) {
            return redirect()->route('pricing');
        }

        return redirect()->intended($user->defaultAuthenticatedPath());
    }
}
