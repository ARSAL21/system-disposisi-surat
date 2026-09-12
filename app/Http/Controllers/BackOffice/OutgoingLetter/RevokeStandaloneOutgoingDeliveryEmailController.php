<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\RevokeStandaloneOutgoingDeliveryEmail;
use App\Http\Controllers\Controller;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class RevokeStandaloneOutgoingDeliveryEmailController extends Controller
{
    public function __invoke(Request $request, OutgoingLetter $outgoingLetter, RevokeStandaloneOutgoingDeliveryEmail $action): RedirectResponse
    {
        Gate::authorize('revokeStandaloneDeliveryEmail', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter);

        return back()->with('success', 'Tautan unduh aman telah dicabut.');
    }
}
