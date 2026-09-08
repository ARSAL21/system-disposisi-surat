<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\FinalizeLetterResponseDossier;
use App\Http\Controllers\Controller;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class FinalizeLetterResponseDossierController extends Controller
{
    public function __invoke(
        Request $request,
        LetterResponseDossier $letterResponseDossier,
        FinalizeLetterResponseDossier $action,
    ): RedirectResponse {
        Gate::authorize('authorizeResponse', $letterResponseDossier);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $action->execute($actor, $letterResponseDossier);

        return to_route('back-office.letter-responses.show', $letterResponseDossier)->with('success', 'Rencana balasan berhasil difinalisasi.');
    }
}
