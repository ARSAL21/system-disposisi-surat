<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\DeliverOutgoingLetter;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\DeliverOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class DeliverOutgoingLetterController extends Controller
{
    public function __invoke(
        DeliverOutgoingLetterRequest $request,
        OutgoingLetter $outgoingLetter,
        DeliverOutgoingLetter $action,
    ): RedirectResponse {
        Gate::authorize('deliver', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengiriman surat resmi berhasil dicatat.']);

        return back();
    }
}
