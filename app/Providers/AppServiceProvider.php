<?php

namespace App\Providers;

use App\Authorization\AuthorizationCatalog;
use App\Enums\AccountType;
use App\Listeners\RecordUserLoginEvent;
use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\InstructionLabel;
use App\Models\LetterResponseDossier;
use App\Models\LetterRoute;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterTemplate;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Models\UserInvitation;
use App\Policies\AuditLogPolicy;
use App\Policies\DispositionPolicy;
use App\Policies\DispositionRecipientPolicy;
use App\Policies\IncomingLetterPolicy;
use App\Policies\InstructionLabelPolicy;
use App\Policies\LetterResponseDossierPolicy;
use App\Policies\LetterRoutePolicy;
use App\Policies\OutgoingLetterPolicy;
use App\Policies\OutgoingLetterTemplatePolicy;
use App\Policies\RolePolicy;
use App\Policies\StandaloneOutgoingDraftPolicy;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        Event::listen(
            Login::class,
            RecordUserLoginEvent::class,
        );
    }

    private function configureAuthorizationPolicies(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(IncomingLetter::class, IncomingLetterPolicy::class);
        Gate::policy(Disposition::class, DispositionPolicy::class);
        Gate::policy(DispositionRecipient::class, DispositionRecipientPolicy::class);
        Gate::policy(InstructionLabel::class, InstructionLabelPolicy::class);
        Gate::policy(LetterRoute::class, LetterRoutePolicy::class);
        Gate::policy(LetterResponseDossier::class, LetterResponseDossierPolicy::class);
        Gate::policy(OutgoingLetter::class, OutgoingLetterPolicy::class);
        Gate::policy(OutgoingLetterTemplate::class, OutgoingLetterTemplatePolicy::class);
        Gate::policy(StandaloneOutgoingDraft::class, StandaloneOutgoingDraftPolicy::class);
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

        Route::bind('managedUser', fn (string $value): User => User::query()
            ->whereKey($value)
            ->firstOrFail());

        Route::bind('userInvitation', fn (string $value): UserInvitation => UserInvitation::query()
            ->whereKey($value)
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

        RateLimiter::for('outgoing-letter-verification', fn (Request $request): array => [
            Limit::perMinute(60)->by('outgoing-letter-verification:ip:'.$request->ip()),
        ]);

        RateLimiter::for('outgoing-delivery-link', fn (Request $request): array => [
            Limit::perMinute(30)->by('outgoing-delivery-link:ip:'.$request->ip()),
        ]);

        RateLimiter::for('document-version-upload', fn (Request $request): array => [
            Limit::perHour(10)->by('document-version-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('document-version-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('manual-intake-upload', fn (Request $request): array => [
            Limit::perHour(10)->by('manual-intake-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('manual-intake-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('letter-routing-create', fn (Request $request): array => [
            Limit::perMinute(30)->by('letter-routing-create:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(60)->by('letter-routing-create:ip:'.$request->ip()),
        ]);

        RateLimiter::for('disposition-create', fn (Request $request): array => [
            Limit::perMinute(30)->by('disposition-create:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(60)->by('disposition-create:ip:'.$request->ip()),
        ]);

        RateLimiter::for('disposition-branch-mutation', fn (Request $request): array => [
            Limit::perMinute(60)->by('disposition-branch-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(120)->by('disposition-branch-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('report-export', fn (Request $request): array => [
            Limit::perMinute(10)->by('report-export:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(30)->by('report-export:ip:'.$request->ip()),
        ]);

        RateLimiter::for('letter-response-upload', fn (Request $request): array => [
            Limit::perHour(20)->by('letter-response-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(60)->by('letter-response-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('letter-response-mutation', fn (Request $request): array => [
            Limit::perMinute(60)->by('letter-response-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(120)->by('letter-response-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('outgoing-letter-mutation', fn (Request $request): array => [
            Limit::perMinute(60)->by('outgoing-letter-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(120)->by('outgoing-letter-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('outgoing-letter-upload', fn (Request $request): array => [
            Limit::perHour(20)->by('outgoing-letter-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(60)->by('outgoing-letter-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('outgoing-template-upload', fn (Request $request): array => [
            Limit::perHour(10)->by('outgoing-template-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('outgoing-template-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('standalone-outgoing-upload', fn (Request $request): array => [
            Limit::perHour(20)->by('standalone-outgoing-upload:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(60)->by('standalone-outgoing-upload:ip:'.$request->ip()),
        ]);

        RateLimiter::for('standalone-outgoing-mutation', fn (Request $request): array => [
            Limit::perMinute(60)->by('standalone-outgoing-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(120)->by('standalone-outgoing-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('user-invitation-create', fn (Request $request): array => [
            Limit::perHour(10)->by('user-invitation-create:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perHour(30)->by('user-invitation-create:ip:'.$request->ip()),
        ]);

        RateLimiter::for('user-invitation-accept', fn (Request $request): array => [
            Limit::perMinute(10)->by('user-invitation-accept:ip:'.$request->ip()),
        ]);

        RateLimiter::for('user-security-mutation', fn (Request $request): array => [
            Limit::perMinute(30)->by('user-security-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(60)->by('user-security-mutation:ip:'.$request->ip()),
        ]);

        RateLimiter::for('user-status-mutation', fn (Request $request): array => [
            Limit::perMinute(30)->by('user-status-mutation:user:'.$request->user()?->getAuthIdentifier()),
            Limit::perMinute(60)->by('user-status-mutation:ip:'.$request->ip()),
        ]);
    }
}
