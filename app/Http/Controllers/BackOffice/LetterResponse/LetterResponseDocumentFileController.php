<?php

namespace App\Http\Controllers\BackOffice\LetterResponse;

use App\Http\Controllers\Controller;
use App\LetterResponses\LetterResponseScopeQuery;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\User;
use App\Services\PrivateDocumentResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LetterResponseDocumentFileController extends Controller
{
    public function preview(
        LetterResponseDossier $letterResponseDossier,
        LetterResponseDocumentVersion $letterResponseDocumentVersion,
        PrivateDocumentResponse $documentResponse,
        LetterResponseScopeQuery $scopeQuery,
        Request $request,
    ): StreamedResponse {
        Gate::authorize('view', $letterResponseDossier);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($scopeQuery->canViewDocumentVersion(
            $user,
            $letterResponseDossier,
            $letterResponseDocumentVersion,
        ), 404);

        return $documentResponse->previewLetterResponseDocument($letterResponseDossier, $letterResponseDocumentVersion);
    }

    public function download(
        LetterResponseDossier $letterResponseDossier,
        LetterResponseDocumentVersion $letterResponseDocumentVersion,
        PrivateDocumentResponse $documentResponse,
        LetterResponseScopeQuery $scopeQuery,
        Request $request,
    ): StreamedResponse {
        Gate::authorize('view', $letterResponseDossier);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($scopeQuery->canViewDocumentVersion(
            $user,
            $letterResponseDossier,
            $letterResponseDocumentVersion,
        ), 404);

        return $documentResponse->downloadLetterResponseDocument($letterResponseDossier, $letterResponseDocumentVersion);
    }
}
