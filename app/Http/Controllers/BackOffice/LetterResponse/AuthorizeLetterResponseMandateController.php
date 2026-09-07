<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Actions\AuthorizeLetterResponseMandate;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\LetterResponse\AuthorizeLetterResponseMandateRequest;
use App\Models\LetterResponseDossier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class AuthorizeLetterResponseMandateController extends Controller
{
    public function __invoke(
        AuthorizeLetterResponseMandateRequest $request,
        LetterResponseDossier $letterResponseDossier,
        AuthorizeLetterResponseMandate $action,
    ): RedirectResponse {
        Gate::authorize('authorizeResponse', $letterResponseDossier);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->execute(
            $actor,
            $letterResponseDossier,
            (string) $request->validated('source_version_public_id'),
            (string) $request->validated('signatory_position_code'),
            (string) $request->validated('subject'),
        );

        return to_route('back-office.letter-responses.show', $letterResponseDossier)->with('success', 'Mandat balasan berhasil dibuat.');
    }
}
