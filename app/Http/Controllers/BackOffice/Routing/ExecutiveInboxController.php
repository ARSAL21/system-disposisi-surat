<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Routing\ListExecutiveInboxRequest;
use App\Models\LetterRoute;
use App\Models\User;
use App\Routing\ExecutiveInboxQuery;
use App\Routing\LetterRoutingPresenter;
use App\Routing\LetterRoutingQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveInboxController extends Controller
{
    public function index(
        ListExecutiveInboxRequest $request,
        ExecutiveInboxQuery $inboxQuery,
        LetterRoutingPresenter $presenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();
        $paginator = $inboxQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('back-office/executive/inbox/Index', [
            'inbox' => [
                'data' => $paginator->getCollection()
                    ->map(fn (LetterRoute $letterRoute): array => $presenter->inboxRoute($letterRoute))
                    ->values()
                    ->all(),
                'pagination' => $this->pagination($paginator),
            ],
            'summary' => $inboxQuery->summary($user),
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.executive.inbox.index'),
            ],
            'preview' => false,
        ]);
    }

    public function show(
        LetterRoute $letterRoute,
        LetterRoutingQuery $routingQuery,
        LetterRoutingPresenter $presenter,
    ): Response {
        Gate::authorize('viewInbox', $letterRoute);
        $letterRoute->load([
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'routedBy:id,name',
            'routedByPositionAssignment.position.organizationalUnit:id,name',
            'incomingLetter' => fn ($letter) => $letter->with($routingQuery->relations()),
        ]);

        return Inertia::render('back-office/executive/inbox/Show', [
            'route' => $presenter->inboxRoute($letterRoute),
            'routes' => [
                'index' => route('back-office.executive.inbox.index'),
            ],
            'preview' => false,
        ]);
    }

    /**
     * @param  LengthAwarePaginator<int, LetterRoute>  $paginator
     * @return array<string, int|string|null>
     */
    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
            'total' => $paginator->total(),
            'previous_url' => $paginator->previousPageUrl(),
            'next_url' => $paginator->nextPageUrl(),
        ];
    }
}
