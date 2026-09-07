<?php

namespace App\Http\Controllers\BackOffice\Disposition;

use App\Dispositions\DispositionInboxQuery;
use App\Dispositions\DispositionPresenter;
use App\Enums\DispositionRecipientStatus;
use App\Exceptions\DispositionStateConflict;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Disposition\ListDispositionInboxRequest;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\InstructionLabel;
use App\Models\User;
use App\Services\SectionHeadDispositionTargetResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DispositionInboxController extends Controller
{
    public function index(
        ListDispositionInboxRequest $request,
        DispositionInboxQuery $inboxQuery,
        DispositionPresenter $presenter,
    ): Response {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->filters();
        $paginator = $inboxQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('back-office/dispositions/inbox/Index', [
            'inbox' => [
                'data' => $paginator->getCollection()
                    ->map(fn (DispositionRecipient $recipient): array => $presenter->inboxRecipient($recipient))
                    ->values()
                    ->all(),
                'pagination' => $this->pagination($paginator),
            ],
            'summary' => $inboxQuery->summary($user),
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.dispositions.inbox.index'),
            ],
            'preview' => false,
        ]);
    }

    public function show(
        Request $request,
        DispositionRecipient $dispositionRecipient,
        DispositionInboxQuery $inboxQuery,
        DispositionPresenter $presenter,
        SectionHeadDispositionTargetResolver $sectionHeadTargetResolver,
    ): Response {
        Gate::authorize('viewInbox', $dispositionRecipient);
        $dispositionRecipient->load($inboxQuery->relations());
        $forwardedDisposition = $this->forwardedDisposition($dispositionRecipient);
        /** @var User $user */
        $user = $request->user();
        $canForward = $forwardedDisposition === null
            && $dispositionRecipient->status->value === 'PENDING'
            && Gate::allows('forwardDisposition', $dispositionRecipient);
        $canProcess = Gate::allows('startBranch', $dispositionRecipient);
        $isSectionHeadBranch = $dispositionRecipient->recipientPosition->positionLevel->code === 'SECTION_HEAD';
        $canStart = $isSectionHeadBranch
            && $canProcess
            && $dispositionRecipient->status === DispositionRecipientStatus::Pending;
        $canAddFollowUp = $isSectionHeadBranch
            && $canProcess
            && $dispositionRecipient->status === DispositionRecipientStatus::InProgress;
        $canComplete = $isSectionHeadBranch
            && $canProcess
            && in_array($dispositionRecipient->status, [
                DispositionRecipientStatus::Pending,
                DispositionRecipientStatus::InProgress,
            ], true);

        return Inertia::render('back-office/dispositions/inbox/Show', [
            'disposition' => $presenter->inboxRecipient($dispositionRecipient),
            'sectionHeadPositions' => $canForward
                ? $presenter->sectionHeadPositions($sectionHeadTargetResolver->options(
                    (int) $user->getKey(),
                    (int) $dispositionRecipient->disposition->incoming_letter_id,
                ))
                : [],
            'instructionLabels' => $canForward
                ? $presenter->instructionOptions(
                    InstructionLabel::query()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get(),
                )
                : [],
            'forwardedDisposition' => $forwardedDisposition instanceof Disposition
                ? $presenter->forwardedDisposition($forwardedDisposition)
                : null,
            'branch' => $isSectionHeadBranch
                ? $presenter->branchLifecycle($dispositionRecipient)
                : null,
            'branchMonitor' => ! $isSectionHeadBranch && $forwardedDisposition instanceof Disposition
                ? $presenter->assistantBranchMonitor($forwardedDisposition)
                : null,
            'capabilities' => [
                'can_forward_disposition' => $canForward,
                'can_start_branch' => $canStart,
                'can_add_follow_up' => $canAddFollowUp,
                'can_complete_branch' => $canComplete,
            ],
            'routes' => array_filter([
                'index' => route('back-office.dispositions.inbox.index'),
                'store' => $canForward
                    ? route('back-office.dispositions.inbox.forward.store', $dispositionRecipient)
                    : null,
                'start' => $canStart
                    ? route('back-office.dispositions.inbox.branch.start', $dispositionRecipient)
                    : null,
                'follow_up' => $canAddFollowUp
                    ? route('back-office.dispositions.inbox.branch.follow-ups.store', $dispositionRecipient)
                    : null,
                'complete' => $canComplete
                    ? route('back-office.dispositions.inbox.branch.complete', $dispositionRecipient)
                    : null,
            ], static fn (?string $url): bool => $url !== null),
            'preview' => false,
        ]);
    }

    private function forwardedDisposition(DispositionRecipient $recipient): ?Disposition
    {
        $dispositions = $recipient->childDispositions()
            ->with([
                'recipients.recipientPosition.positionLevel:id,code',
                'recipients.recipientPosition.organizationalUnit:id,name',
                'recipients.recipientPosition.activeAssignment.user:id,name,account_type,is_active,email_verified_at',
                'recipients.completedBy:id,name',
                'recipients.completedByPositionAssignment.position.organizationalUnit:id,name',
                'recipients.followUps' => fn ($followUps) => $followUps
                    ->orderBy('created_at')
                    ->orderBy('id'),
                'recipients.followUps.createdBy:id,name',
                'recipients.followUps.createdByPositionAssignment.position.organizationalUnit:id,name',
                'instructionLabels:id,code,name,description,sort_order,is_active',
                'createdBy:id,name',
                'createdByPositionAssignment.position.organizationalUnit:id,name',
            ])
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($dispositions->count() > 1) {
            throw DispositionStateConflict::staleSource();
        }

        return $dispositions->first();
    }

    /**
     * @param  LengthAwarePaginator<int, DispositionRecipient>  $paginator
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
