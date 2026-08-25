<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN);

        return match ($user->account_type) {
            AccountType::PublicAccount => to_route('public.dashboard'),
            AccountType::InternalAccount => to_route('internal.dashboard'),
        };
    }
}
