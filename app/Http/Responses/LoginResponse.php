<?php

namespace App\Http\Responses;

use App\Services\ActivityLogService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Get the response for a successful login.
     */
    public function toResponse($request)
    {
        if ($request->user() && ! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Log login activity
        ActivityLogService::logLogin();

        return redirect()->intended(config('fortify.home'));
    }
}
