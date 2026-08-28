<?php

use App\Http\Middleware\EnsureBackOfficeLoginIsGuest;
use App\Http\Middleware\EnsureCriticalInternalAccountHasMfa;
use App\Http\Middleware\EnsureMfaIsEnabled;
use App\Http\Middleware\EnsureUserHasAccountType;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'account' => EnsureUserHasAccountType::class,
            'active' => EnsureUserIsActive::class,
            'back-office.guest' => EnsureBackOfficeLoginIsGuest::class,
            'critical-mfa' => EnsureCriticalInternalAccountHasMfa::class,
            'mfa' => EnsureMfaIsEnabled::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('back-office') || $request->is('back-office/*')
                ? route('back-office.login')
                : route('login'),
        );

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
