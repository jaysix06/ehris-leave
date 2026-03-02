<?php

namespace App\Http\Responses;

use App\Services\ActivityLogService;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request)
    {
        // Logout event listener in AppServiceProvider handles most cases
        // This is a fallback that tries to get user ID from auth guard before it's cleared
        // Note: User is already logged out by this point, so we rely on the event listener
        return redirect('/');
    }
}
