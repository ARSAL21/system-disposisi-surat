<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\ReturnOutgoingLetterDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\ReturnOutgoingLetterDocumentRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final class ReturnOutgoingLetterDocumentController extends Controller
{
    public function __invoke(
        ReturnOutgoingLetterDocumentRequest $request,
        OutgoingLetter $outgoingLetter,
        ReturnOutgoingLetterDocument $action,
    ): RedirectResponse {
        Gate::authorize('verify', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $action->execute($user, $outgoingLetter, $request->string('revision_reason')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dokumen dikembalikan untuk diperbaiki tanpa menghapus versi lama.']);

        return back();
    }
}
