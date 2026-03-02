<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PasswordResetOtpController extends Controller
{
    /**
     * Send a one-time password (OTP) to the provided email.
     */
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $throttleKey = 'password-reset-otp|'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Please wait {$seconds} seconds before requesting another OTP.",
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $existingOtp = DB::table('password_reset_otps')
                ->where('email', $email)
                ->first();

            $otp = null;

            if (
                $existingOtp
                && $existingOtp->used_at === null
                && now()->lessThanOrEqualTo($existingOtp->expires_at)
                && $existingOtp->attempts < 5
                && ! empty($existingOtp->otp_code)
            ) {
                try {
                    $otp = decrypt($existingOtp->otp_code);
                } catch (Throwable) {
                    $otp = null;
                }
            }

            if (! is_string($otp) || strlen($otp) !== 6) {
                $otp = (string) random_int(100000, 999999);
            }

            DB::table('password_reset_otps')->updateOrInsert(
                ['email' => $email],
                [
                    'otp_hash' => Hash::make($otp),
                    'otp_code' => encrypt($otp),
                    'attempts' => 0,
                    'expires_at' => now()->addMinutes(10),
                    'used_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $user->notify(new PasswordResetOtpNotification($otp));
        }

        $request->session()->put('otp_email', $email);
        $request->session()->forget('password_reset_verified_email');

        return redirect()
            ->route('password.otp.verify.form')
            ->with('status', 'otp-sent');
    }

    /**
     * Show the OTP verification page.
     */
    public function showVerify(Request $request): RedirectResponse|Response
    {
        $email = $request->session()->get('otp_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/ForgotPasswordOtp', [
            'status' => $request->session()->get('status'),
            'otpEmail' => $email,
        ]);
    }

    /**
     * Verify OTP and proceed to reset-password page.
     */
    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get('otp_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $email = Str::lower(trim($email));
        $otpRecord = DB::table('password_reset_otps')->where('email', $email)->first();
        $user = User::query()->where('email', $email)->first();

        if (! $otpRecord || ! $user || $otpRecord->used_at !== null || now()->greaterThan($otpRecord->expires_at)) {
            return back()
                ->withErrors(['otp' => 'The OTP is invalid or expired.']);
        }

        if ($otpRecord->attempts >= 5) {
            return back()
                ->withErrors(['otp' => 'Too many invalid OTP attempts. Please request a new OTP.']);
        }

        $isValidOtp = Hash::check($validated['otp'], $otpRecord->otp_hash);

        if (! $isValidOtp && ! empty($otpRecord->otp_code)) {
            try {
                $isValidOtp = hash_equals(decrypt($otpRecord->otp_code), $validated['otp']);
            } catch (Throwable) {
                $isValidOtp = false;
            }
        }

        if (! $isValidOtp) {
            DB::table('password_reset_otps')
                ->where('email', $email)
                ->increment('attempts');

            return back()
                ->withErrors(['otp' => 'The OTP is invalid or expired.']);
        }

        DB::table('password_reset_otps')
            ->where('email', $email)
            ->update([
                'used_at' => now(),
                'updated_at' => now(),
            ]);

        $request->session()->put('password_reset_verified_email', $email);

        return redirect()->route('password.otp.reset.form');
    }

    /**
     * Show the final password reset page after OTP verification.
     */
    public function showReset(Request $request): RedirectResponse|Response
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        return Inertia::render('auth/ForgotPasswordResetOtp', [
            'otpEmail' => $email,
        ]);
    }

    /**
     * Reset the user's password after OTP has been verified.
     */
    public function reset(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_verified_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        // Log password reset activity - user resetting their own password via OTP
        ActivityLogService::logPasswordReset($user->email);

        DB::table('password_reset_otps')->where('email', $email)->delete();
        $request->session()->forget([
            'otp_email',
            'password_reset_verified_email',
        ]);

        return redirect()->route('password.otp.success');
    }

    /**
     * Show success page and auto-redirect to login.
     */
    public function success(): Response
    {
        return Inertia::render('auth/PasswordResetSuccess');
    }
}
