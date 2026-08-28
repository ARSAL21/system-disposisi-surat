<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaIsEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN);

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_FORBIDDEN, 'Multi-factor authentication is required for this operation.');
        }

        return to_route('security.edit')
            ->with('status', 'Aktifkan dan konfirmasi MFA sebelum mengubah konfigurasi otorisasi.');
    }
}
