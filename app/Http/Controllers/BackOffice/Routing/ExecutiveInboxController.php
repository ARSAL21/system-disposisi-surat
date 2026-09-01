<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Dispositions\DispositionPresenter;
use App\Enums\LetterRouteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Routing\ListExecutiveInboxRequest;
use App\Models\Disposition;
use App\Models\InstructionLabel;
use App\Models\LetterRoute;
use App\Models\User;
use App\Routing\ExecutiveInboxQuery;
use App\Routing\LetterRoutingPresenter;
use App\Routing\LetterRoutingQuery;
use App\Services\AssistantDispositionTargetResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
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
        Request $request,
        LetterRoute $letterRoute,
        LetterRoutingQuery $routingQuery,
        LetterRoutingPresenter $presenter,
        AssistantDispositionTargetResolver $targetResolver,
        DispositionPresenter $dispositionPresenter,
    ): Response {
        /** @var User $actor */
        $actor = $request->user();
        Gate::authorize('viewInbox', $letterRoute);
        $letterRoute->load([
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'routedBy:id,name',
            'routedByPositionAssignment.position.organizationalUnit:id,name',
            'incomingLetter' => fn ($letter) => $letter->with($routingQuery->relations()),
            'disposition.instructionLabels:id,code,name,description,sort_order,is_active',
            'disposition.createdBy:id,name',
            'disposition.createdByPositionAssignment.position.organizationalUnit:id,name',
            'disposition.recipients.recipientPosition.positionLevel:id,code',
            'disposition.recipients.recipientPosition.organizationalUnit:id,name',
            'disposition.recipients.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
        ]);
        $firstDisposition = $letterRoute->disposition;
        $canCreateDisposition = Gate::allows('createDisposition', $letterRoute)
            && $letterRoute->status === LetterRouteStatus::Pending
            && ! $firstDisposition instanceof Disposition;
        $instructionLabels = $canCreateDisposition
            ? InstructionLabel::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();
        $assistantPositions = $canCreateDisposition
            ? $targetResolver->options(
                $letterRoute->recipient_position_id,
                (int) $actor->getKey(),
            )
            : collect();

        return Inertia::render('back-office/executive/inbox/Show', [
            'route' => $presenter->inboxRoute($letterRoute),
            'assistantPositions' => $dispositionPresenter->assistantPositions($assistantPositions),
            'instructionLabels' => $dispositionPresenter->instructionOptions($instructionLabels),
            'firstDisposition' => $firstDisposition instanceof Disposition
                ? $dispositionPresenter->firstDisposition($firstDisposition)
                : null,
            'capabilities' => [
                'can_create_disposition' => $canCreateDisposition,
            ],
            'routes' => [
                'index' => route('back-office.executive.inbox.index'),
                'store' => route('back-office.executive.inbox.dispositions.store', $letterRoute),
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
