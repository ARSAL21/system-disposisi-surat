<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\CreateLetterResponseContribution;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LetterResponse\StoreLetterResponseDocumentRequest;
use App\Models\DispositionRecipient;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class StoreAssistantResponseProposalController extends Controller
{
    public function __invoke(
        StoreLetterResponseDocumentRequest $request,
        LetterResponseDossier $letterResponseDossier,
        DispositionRecipient $dispositionRecipient,
        CreateLetterResponseContribution $action,
    ): RedirectResponse {
        $dossier = $request->dossier();
        Gate::authorize('contributeForPosition', [$dossier, $dispositionRecipient->recipient_position_id]);
        abort_unless(
            $dispositionRecipient->disposition()->where('incoming_letter_id', $dossier->incoming_letter_id)->exists(),
            404,
        );
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->assistantProposal($actor, $dossier, $dispositionRecipient, $request->document(), $request->revisionNote());

        return to_route('back-office.letter-responses.show', $dossier)->with('success', 'Proposal Asisten berhasil disimpan.');
    }
}
