<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Authentication\LoginAccountTypeResolver;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

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
        $this->configureAuthentication();
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure authentication restrictions shared by public and internal users.
     */
    private function configureAuthentication(): void
    {
        $loginAccountTypeResolver = app(LoginAccountTypeResolver::class);

        Fortify::authenticateUsing(function (Request $request) use ($loginAccountTypeResolver): ?User {
            return (new Timebox)->call(function (Timebox $timebox) use ($request, $loginAccountTypeResolver): ?User {
                $accountType = $loginAccountTypeResolver->forPasswordRequest($request);

                if ($accountType === null) {
                    return null;
                }

                $email = Str::lower($request->string(Fortify::username())->toString());

                $user = User::query()
                    ->where('email', $email)
                    ->where('account_type', $accountType->value)
                    ->where('is_active', true)
                    ->first();

                if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
                    return null;
                }

                $timebox->returnEarly();

                return $user;
            }, (int) config('auth.timebox_duration', 200_000));
        });

        Passkeys::authorizeLoginUsing(
            function (Request $request, PasskeyUser $user, Passkey $_passkey) use ($loginAccountTypeResolver): bool {
                $accountType = $loginAccountTypeResolver->forPasskeyRequest($request);

                return $accountType !== null
                    && $user instanceof User
                    && $user->is_active
                    && $user->account_type === $accountType;
            },
        );
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
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render(
            $request->routeIs('back-office.login') ? 'back-office/auth/Login' : 'auth/Login',
            [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->session()->get('status'),
            ],
        ));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(function (Request $request) {
            $backOffice = $request->routeIs('back-office.password.confirm');

            if ($backOffice) {
                return redirect()->route('back-office.dashboard');
            }

            return redirect()->route('profile.edit');
        });
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

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
