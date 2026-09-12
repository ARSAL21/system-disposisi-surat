<?php

namespace App\Http\Controllers\BackOffice\Routing;

use App\Dispositions\DispositionPresenter;
use App\Enums\LetterRouteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Routing\ListExecutiveInboxRequest;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
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
        DispositionPresenter $dispositionPresenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();
        $paginator = $inboxQuery->paginate($user, $filters);
        $entries = $inboxQuery->hydrate($paginator);

        return Inertia::render('back-office/executive/inbox/Index', [
            'inbox' => [
                'data' => $entries
                    ->map(function (array $entry) use ($presenter, $dispositionPresenter): array {
                        if ($entry['model'] instanceof LetterRoute) {
                            return [
                                'entry_type' => 'DIRECT_ROUTE',
                                'entry_id' => (int) $entry['model']->getKey(),
                                'source_label' => 'Langsung dari Bagian Umum',
                                ...$presenter->inboxRoute($entry['model']),
                                'branch_progress' => $dispositionPresenter->executiveBranchProgress(
                                    $entry['model']->disposition,
                                ),
                            ];
                        }

                        $recipient = $entry['model'];
                        $presented = $dispositionPresenter->executiveInboxRecipient($recipient);
                        $childDisposition = $recipient->childDispositions->first();

                        return [
                            'entry_type' => 'MAYOR_DISPOSITION',
                            'entry_id' => (int) $recipient->getKey(),
                            'source_label' => 'Arahan Wali Kota',
                            'letter' => $presented['letter'],
                            'received_in_inbox_at' => $presented['received_at'],
                            'branch_progress' => $dispositionPresenter->executiveBranchProgress(
                                $childDisposition instanceof Disposition ? $childDisposition : null,
                            ),
                            'links' => ['show' => route('back-office.executive.inbox.recipient.show', $recipient)],
                        ];
                    })
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
            'disposition.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'disposition.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
            'disposition.recipients.childDispositions.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'disposition.recipients.childDispositions.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
        ]);
        $firstDisposition = $letterRoute->disposition;
        $canCreateDisposition = Gate::allows('createDisposition', $letterRoute)
            && $letterRoute->status === LetterRouteStatus::Pending
            && ! $firstDisposition instanceof Disposition;
        $canForwardToSekda = Gate::allows('forwardToSekda', $letterRoute)
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
            'branchProgress' => $dispositionPresenter->executiveBranchProgress($firstDisposition),
            'capabilities' => [
                'can_create_disposition' => $canCreateDisposition,
                'can_forward_to_sekda' => $canForwardToSekda,
            ],
            'routes' => [
                'index' => route('back-office.executive.inbox.index'),
                'store' => route('back-office.executive.inbox.dispositions.store', $letterRoute),
                'forward_to_sekda' => route('back-office.executive.inbox.forward-to-sekda.store', $letterRoute),
            ],
            'preview' => false,
        ]);
    }

    public function showRecipient(
        Request $request,
        DispositionRecipient $dispositionRecipient,
        LetterRoutingQuery $routingQuery,
        DispositionPresenter $dispositionPresenter,
        AssistantDispositionTargetResolver $targetResolver,
    ): Response {
        /** @var User $actor */
        $actor = $request->user();
        Gate::authorize('viewExecutiveInbox', $dispositionRecipient);
        $dispositionRecipient->load([
            'recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'disposition.instructionLabels:id,code,name,description,sort_order,is_active',
            'disposition.createdBy:id,name',
            'disposition.createdByPositionAssignment.position.organizationalUnit:id,name',
            'disposition.incomingLetter' => fn ($letter) => $letter->with($routingQuery->relations()),
            'childDispositions.instructionLabels:id,code,name,description,sort_order,is_active',
            'childDispositions.createdBy:id,name',
            'childDispositions.createdByPositionAssignment.position.organizationalUnit:id,name',
            'childDispositions.recipients.recipientPosition.positionLevel:id,code',
            'childDispositions.recipients.recipientPosition.organizationalUnit:id,name',
            'childDispositions.recipients.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
            'childDispositions.recipients.childDispositions.recipients:id,disposition_id,recipient_position_id,status',
            'childDispositions.recipients.childDispositions.recipients.recipientPosition.positionLevel:id,code',
        ]);
        $childDisposition = $dispositionRecipient->childDispositions->first();
        $canCreateDisposition = Gate::allows('forwardToAssistants', $dispositionRecipient)
            && $dispositionRecipient->status->value === 'PENDING'
            && ! $childDisposition instanceof Disposition;
        $instructionLabels = $canCreateDisposition
            ? InstructionLabel::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
        $assistantPositions = $canCreateDisposition
            ? $targetResolver->options($dispositionRecipient->recipient_position_id, (int) $actor->getKey())
            : collect();
        $presentedRecipient = $dispositionPresenter->executiveInboxRecipient($dispositionRecipient);

        return Inertia::render('back-office/executive/inbox/Show', [
            'route' => [
                'entry_type' => 'MAYOR_DISPOSITION',
                'entry_id' => (int) $dispositionRecipient->getKey(),
                'source_label' => 'Arahan Wali Kota',
                'letter' => $presentedRecipient['letter'],
                'received_in_inbox_at' => $presentedRecipient['received_at'],
                'branch_progress' => $dispositionPresenter->executiveBranchProgress($childDisposition instanceof Disposition ? $childDisposition : null),
                'links' => ['show' => route('back-office.executive.inbox.recipient.show', $dispositionRecipient)],
            ],
            'assistantPositions' => $dispositionPresenter->assistantPositions($assistantPositions),
            'instructionLabels' => $dispositionPresenter->instructionOptions($instructionLabels),
            'firstDisposition' => $childDisposition instanceof Disposition
                ? $dispositionPresenter->firstDisposition($childDisposition)
                : null,
            'branchProgress' => $dispositionPresenter->executiveBranchProgress($childDisposition instanceof Disposition ? $childDisposition : null),
            'capabilities' => [
                'can_create_disposition' => $canCreateDisposition,
                'can_forward_to_sekda' => false,
            ],
            'routes' => [
                'index' => route('back-office.executive.inbox.index'),
                'store' => route('back-office.executive.inbox.recipient.dispositions.store', $dispositionRecipient),
            ],
            'preview' => false,
        ]);
    }

    /**
     * @param  LengthAwarePaginator<int, object>  $paginator
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
