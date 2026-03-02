<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class CustomRegisterResponse implements RegisterResponseContract
{
    /**
     * After registration, inactive users are not allowed to reach the dashboard.
     * Log them out and redirect to login with a "pending activation" message.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request): Response
    {
        $user = Auth::user();

        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Registration successful. Your account is pending activation by an administrator.';

            if ($request->wantsJson()) {
                return new JsonResponse([
                    'redirect' => route('login'),
                    'message' => $message,
                ], 201);
            }

            return redirect()->route('login')
                ->with('status', $message);
        }

        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        return redirect()->intended(Fortify::redirects('register'));
    }
}
