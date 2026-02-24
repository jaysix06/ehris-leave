<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    /**
     * Get the response for a verified email request.
     */
    public function toResponse($request)
    {
        return redirect()->route('verification.success');
    }
}
