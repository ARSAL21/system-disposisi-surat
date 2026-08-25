<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAccountType
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $accountType): Response
    {
        $expectedAccountType = AccountType::tryFrom($accountType)
            ?? throw new InvalidArgumentException('Unsupported account type middleware parameter.');

        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->account_type === $expectedAccountType,
            Response::HTTP_NOT_FOUND,
        );

        return $next($request);
    }
}
