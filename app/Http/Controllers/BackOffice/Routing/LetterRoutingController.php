<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Routing\ListLetterRoutingRequest;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Routing\LetterRoutingPresenter;
use App\Routing\LetterRoutingQuery;
use App\Services\ExecutiveRoutingTargetResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LetterRoutingController extends Controller
{
    public function index(
        ListLetterRoutingRequest $request,
        LetterRoutingQuery $routingQuery,
        LetterRoutingPresenter $presenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();
        $paginator = $routingQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('back-office/letter-routing/Index', [
            'letters' => [
                'data' => $paginator->getCollection()
                    ->map(fn (IncomingLetter $letter): array => $presenter->routingLetter($letter))
                    ->values()
                    ->all(),
                'pagination' => $this->pagination($paginator),
            ],
            'summary' => $routingQuery->summary($user),
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.letter-routing.index'),
            ],
            'preview' => false,
        ]);
    }

    public function show(
        IncomingLetter $incomingLetter,
        LetterRoutingQuery $routingQuery,
        LetterRoutingPresenter $presenter,
        ExecutiveRoutingTargetResolver $targetResolver,
    ): Response {
        Gate::authorize('viewRouting', $incomingLetter);
        $incomingLetter->load($routingQuery->relations());

        return Inertia::render('back-office/letter-routing/Show', [
            'letter' => $presenter->routingLetter($incomingLetter),
            'executivePositions' => $presenter->executivePositions($targetResolver->options()),
            'capabilities' => [
                'can_route' => Gate::allows('createRoute', $incomingLetter),
            ],
            'routes' => [
                'index' => route('back-office.letter-routing.index'),
                'store' => route('back-office.letter-routing.store', $incomingLetter),
            ],
            'preview' => false,
        ]);
    }

    /**
     * @param  LengthAwarePaginator<int, IncomingLetter>  $paginator
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
