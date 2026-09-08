<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\ReturnLetterResponseDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LetterResponse\ReturnLetterResponseDocumentRequest;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDossier;
use App\Models\User;
use App\Services\LetterResponseReviewerPositionResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class ReturnLetterResponseDocumentController extends Controller
{
    public function __invoke(
        ReturnLetterResponseDocumentRequest $request,
        LetterResponseDossier $letterResponseDossier,
        LetterResponseDocument $letterResponseDocument,
        LetterResponseReviewerPositionResolver $reviewerResolver,
        ReturnLetterResponseDocument $action,
    ): RedirectResponse {
        abort_unless((int) $letterResponseDocument->letter_response_dossier_id === (int) $letterResponseDossier->getKey(), 404);
        $reviewerPositionId = $reviewerResolver->resolve($letterResponseDossier, $letterResponseDocument);
        Gate::authorize('reviewForPosition', [$letterResponseDossier, $reviewerPositionId]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->execute($actor, $letterResponseDossier, $letterResponseDocument, $request->reason());

        return to_route('back-office.letter-responses.show', $letterResponseDossier)->with('success', 'Dokumen dikembalikan untuk direvisi.');
    }
}
