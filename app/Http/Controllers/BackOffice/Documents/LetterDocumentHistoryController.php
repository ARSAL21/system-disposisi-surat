<?php

namespace App\Http\Controllers\BackOffice\Documents;

use App\Documents\DocumentVersionHistoryPresenter;
use App\Documents\DocumentVersionHistoryQuery;
use App\Enums\IncomingLetterStatus;
use App\Http\Controllers\Controller;
use App\Models\IncomingLetter;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LetterDocumentHistoryController extends Controller
{
    public function __invoke(
        IncomingLetter $incomingLetter,
        DocumentVersionHistoryQuery $historyQuery,
        DocumentVersionHistoryPresenter $presenter,
    ): Response {
        Gate::authorize('viewDocumentVersions', $incomingLetter);

        $history = $historyQuery->execute($incomingLetter);
        $canCreateVersion = Gate::allows('createDocumentVersion', $incomingLetter)
            && $incomingLetter->status === IncomingLetterStatus::Registered;
        $currentVersionNumber = (int) $history['versions']->max('version_number');

        return Inertia::render('back-office/letters/documents/Index', [
            'letter' => $presenter->letter($incomingLetter),
            'versions' => $presenter->versions($history['versions'], $history['audits']),
            'capabilities' => [
                'can_create_version' => $canCreateVersion,
            ],
            'next_version_number' => $currentVersionNumber + 1,
            'routes' => [
                'archive' => route('back-office.documents.index'),
                'store' => route('back-office.letters.documents.store', $incomingLetter),
            ],
            'preview' => false,
        ]);
    }
}
