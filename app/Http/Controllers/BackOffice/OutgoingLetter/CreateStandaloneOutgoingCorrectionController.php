<?php

namespace App\Http\Controllers\BackOffice\OutgoingLetter;

use App\Actions\CreateStandaloneOutgoingCorrectionDraft;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\OutgoingLetter\CreateStandaloneOutgoingCorrectionRequest;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CreateStandaloneOutgoingCorrectionController extends Controller
{
    public function __invoke(CreateStandaloneOutgoingCorrectionRequest $request, OutgoingLetter $outgoingLetter, CreateStandaloneOutgoingCorrectionDraft $action): RedirectResponse
    {
        Gate::authorize('createStandaloneCorrection', $outgoingLetter);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $draft = $action->execute($user, $outgoingLetter, $request->validated('correction_reason'));

        return to_route('back-office.standalone-outgoing.show', $draft)
            ->with('success', 'Konsep surat koreksi dibuat. Nomor dan dokumen surat lama tetap tersimpan.');
    }
}
