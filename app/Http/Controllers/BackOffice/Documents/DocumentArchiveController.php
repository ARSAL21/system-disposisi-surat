<?php

namespace App\Http\Controllers\BackOffice\Documents;

use App\Documents\DocumentArchivePresenter;
use App\Documents\DocumentArchiveQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Documents\ListDocumentArchiveRequest;
use App\Models\IncomingLetter;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DocumentArchiveController extends Controller
{
    public function __invoke(
        ListDocumentArchiveRequest $request,
        DocumentArchiveQuery $archiveQuery,
        DocumentArchivePresenter $presenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();
        $paginator = $archiveQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('back-office/documents/Index', [
            'documents' => [
                'data' => $paginator->getCollection()
                    ->map(fn (IncomingLetter $letter): array => $presenter->present($letter))
                    ->values()
                    ->all(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem() ?? 0,
                    'to' => $paginator->lastItem() ?? 0,
                    'total' => $paginator->total(),
                    'previous_url' => $paginator->previousPageUrl(),
                    'next_url' => $paginator->nextPageUrl(),
                ],
            ],
            'summary' => $archiveQuery->summary($user),
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.documents.index'),
            ],
            'preview' => false,
        ]);
    }
}
