<?php

namespace App\Http\Responses;

use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Get the response for a successful login.
     */
    public function toResponse($request)
    {
        $user = $request->user();

        // Inactive accounts are not allowed to proceed; they must wait for admin activation.
        if ($user && ! $user->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Your account is pending activation by an administrator.';

            if ($request->wantsJson()) {
                return response()->json([
                    'redirect' => route('login'),
                    'message' => $message,
                ], 200);
            }

            return redirect()->route('login')->with('status', $message);
        }

        // Log login activity
        ActivityLogService::logLogin();

        // Set flag to show popups on next dashboard visit (only after login)
        $request->session()->put('show_popups_after_login', true);

        $role = $user->role ?? 'Employee';
        $roleHome = config('ehris.role_home', []);
        $path = is_array($roleHome) && isset($roleHome[$role])
            ? $roleHome[$role]
            : config('fortify.home');

        return redirect()->intended($path);
    }
}
