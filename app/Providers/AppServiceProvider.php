<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureRateLimiting();
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
    }
}
