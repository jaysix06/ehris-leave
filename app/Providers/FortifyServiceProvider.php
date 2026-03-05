<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\EmploymentStatus;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure authentication to support the activation flow.
     *
     * - Users should log in using their official DepEd email after activation.
     * - During transition/debugging, allow matching either `email` (official)
     *   or `personal_email` (registration email), but still require password match.
     * - Inactive users are handled by the custom LoginResponse (shows pending message).
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $rawEmail = (string) $request->input('email', '');
            $email = Str::lower(trim($rawEmail));
            $password = (string) $request->input('password', '');

            if ($email === '' || $password === '') {
                Log::info('[Auth] Missing email/password', [
                    'email' => $email,
                ]);
                return null;
            }

            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->orWhereRaw('LOWER(TRIM(personal_email)) = ?', [$email])
                ->first();

            if (! $user || ! is_string($user->password) || $user->password === '') {
                Log::info('[Auth] User not found or no password', [
                    'email' => $email,
                    'found' => (bool) $user,
                    'userId' => $user?->getKey(),
                    'active' => $user?->active,
                    'has_password' => $user ? (is_string($user->password) && $user->password !== '') : false,
                    'db_email' => $user?->email,
                    'db_personal_email' => $user?->personal_email,
                ]);
                return null;
            }

            $ok = Hash::check($password, $user->password);
            Log::info('[Auth] Password check', [
                'email' => $email,
                'userId' => $user->getKey(),
                'active' => (bool) $user->active,
                'ok' => $ok,
                'hash_prefix' => substr($user->password, 0, 7),
            ]);

            return $ok ? $user : null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(function (Request $request) {
            $request->session()->forget([
                'otp_email',
                'password_reset_verified_email',
            ]);

            return Inertia::render('auth/ForgotPassword', [
                'status' => $request->session()->get('status'),
            ]);
        });

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'employmentStatuses' => EmploymentStatus::orderBy('id')->pluck('emp_status')->values()->all(),
            'districts' => BusinessUnit::orderBy('id')->get()->map(fn ($row) => [
                'id' => $row->BusinessUnitId,
                'name' => $row->BusinessUnit,
            ])->values()->all(),
            // Each station carries the district (BusinessUnitId) it belongs to via business_id.
            'stations' => Department::orderBy('id')->get()->map(fn ($row) => [
                'id' => $row->department_id,
                'name' => $row->department_name,
                'district_id' => $row->business_id,
            ])->values()->all(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
