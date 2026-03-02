<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogLogoutActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a logout request
        if ($request->routeIs('logout') && $request->isMethod('post')) {
            $user = Auth::user();

            // Log logout activity before the logout happens
            if ($user) {
                ActivityLogService::logLogout($user->userId);
            }
        }

        return $next($request);
    }
}
