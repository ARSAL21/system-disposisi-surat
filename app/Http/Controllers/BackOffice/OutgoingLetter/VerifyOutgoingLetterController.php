<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\VerifyOutgoingLetterDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\VerifyOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class VerifyOutgoingLetterController extends Controller
{
    public function __invoke(
        VerifyOutgoingLetterRequest $request,
        OutgoingLetter $outgoingLetter,
        VerifyOutgoingLetterDocument $action,
    ): RedirectResponse {
        Gate::authorize('verify', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $note = $request->validated('verification_note');
        $action->execute($user, $outgoingLetter, is_string($note) ? $note : null);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen final telah diverifikasi secara administratif.']);

        return back();
    }
}
