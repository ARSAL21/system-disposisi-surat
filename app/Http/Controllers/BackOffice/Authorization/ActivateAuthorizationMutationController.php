<?php

namespace App\Http\Controllers\BackOffice\Authorization;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ActivateAuthorizationMutationController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Mode perubahan aktif selama 15 menit.',
        ]);

        return to_route('back-office.authorization.index');
    }
}
