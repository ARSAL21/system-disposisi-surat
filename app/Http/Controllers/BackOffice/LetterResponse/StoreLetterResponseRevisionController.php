<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\CreateLetterResponseContribution;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LetterResponse\StoreLetterResponseDocumentRequest;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class StoreLetterResponseRevisionController extends Controller
{
    public function __invoke(
        StoreLetterResponseDocumentRequest $request,
        LetterResponseDossier $letterResponseDossier,
        LetterResponseDocument $letterResponseDocument,
        CreateLetterResponseContribution $action,
    ): RedirectResponse {
        $dossier = $request->dossier();
        abort_unless((int) $letterResponseDocument->letter_response_dossier_id === (int) $dossier->getKey(), 404);
        Gate::authorize('contributeForPosition', [$dossier, $letterResponseDocument->owner_position_id]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->revision($actor, $dossier, $letterResponseDocument, $request->document(), $request->revisionNote());

        return to_route('back-office.letter-responses.show', $dossier)->with('success', 'Versi revisi berhasil disimpan.');
    }
}
