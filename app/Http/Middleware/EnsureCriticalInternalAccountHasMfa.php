<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCriticalInternalAccountHasMfa
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN);

        if (! $user->hasRole(RoleName::SuperAdmin->value) || $user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_FORBIDDEN, 'Multi-factor authentication is required for this account.');
        }

        return to_route('security.edit')
            ->with('status', 'Enable and confirm multi-factor authentication before using administrative access.');
    }
}
