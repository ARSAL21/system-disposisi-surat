<?php

namespace App\Providers;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\IncomingLetterPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
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
        $this->configureDefaults();
        $this->configureAuthorizationPolicies();
        $this->configureAuthorizationRouteBindings();
        $this->configureRateLimiting();
    }

    private function configureAuthorizationPolicies(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(IncomingLetter::class, IncomingLetterPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    private function configureAuthorizationRouteBindings(): void
    {
        Route::bind('role', fn (string $value): Role => Role::query()
            ->whereKey($value)
            ->where('guard_name', AuthorizationCatalog::GUARD_NAME)
            ->firstOrFail());

        Route::bind('user', fn (string $value): User => User::query()
            ->whereKey($value)
            ->where('account_type', AccountType::InternalAccount->value)
            ->firstOrFail());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-submission-read', fn (Request $request): array => [
            Limit::perMinute(120)->by('public-submission-read:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(240)->by('public-submission-read:ip:'.$request->ip()),
        ]);

        RateLimiter::for('public-submission-create', fn (Request $request): array => [
            Limit::perHour(10)->by('public-submission-create:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('public-submission-create:ip:'.$request->ip()),
        ]);

        RateLimiter::for('public-submission-mutation', fn (Request $request): array => [
            Limit::perHour(60)->by('public-submission-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(180)->by('public-submission-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('public-submission-upload', fn (Request $request): array => [
            Limit::perHour(20)->by('public-submission-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(60)->by('public-submission-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('public-submission-submit', fn (Request $request): array => [
            Limit::perHour(10)->by('public-submission-submit:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('public-submission-submit:ip:'.$request->ip()),
        ]);

        RateLimiter::for('private-document-access', fn (Request $request): array => [
            Limit::perMinute(60)->by('private-document-access:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(120)->by('private-document-access:ip:'.$request->ip()),
        ]);

        RateLimiter::for('document-version-upload', fn (Request $request): array => [
            Limit::perHour(10)->by('document-version-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('document-version-upload:ip:'.$request->ip()),
        ]);
    }
}
