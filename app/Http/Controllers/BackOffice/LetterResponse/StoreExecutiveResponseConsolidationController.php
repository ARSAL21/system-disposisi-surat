<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\CreateLetterResponseContribution;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LetterResponse\StoreLetterResponseDocumentRequest;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class StoreExecutiveResponseConsolidationController extends Controller
{
    public function __invoke(
        StoreLetterResponseDocumentRequest $request,
        LetterResponseDossier $letterResponseDossier,
        CreateLetterResponseContribution $action,
    ): RedirectResponse {
        $dossier = $request->dossier();
        $positionId = (int) $dossier->incomingLetter->routes()->orderBy('id')->value('recipient_position_id');
        abort_unless($positionId > 0, 404);
        Gate::authorize('contributeForPosition', [$dossier, $positionId]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->executiveConsolidation(
            $actor,
            $dossier,
            $request->document(),
            $request->revisionNote(),
            $request->sourceVersionPublicIds(),
        );

        return to_route('back-office.letter-responses.show', $dossier)->with('success', 'Konsolidasi eksekutif berhasil disimpan.');
    }
}
