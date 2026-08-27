<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackOfficeLoginIsGuest
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        abort_unless(
            $user->isInternalAccount(),
            Response::HTTP_NOT_FOUND,
        );

        return to_route('back-office.dashboard');
    }
}
