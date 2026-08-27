<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BackOfficeEntryController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return to_route('back-office.login');
        }

        abort_unless(
            $user->isInternalAccount(),
            Response::HTTP_NOT_FOUND,
        );

        return to_route('back-office.dashboard');
    }
}
