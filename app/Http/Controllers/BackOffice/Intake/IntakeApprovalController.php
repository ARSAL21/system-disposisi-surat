<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\GetIntakeApprovalWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\ListIntakeApprovalsRequest;
use App\Http\Resources\IntakeApprovalSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\SenderOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IntakeApprovalController extends Controller
{
    public function index(
        ListIntakeApprovalsRequest $request,
        GetIntakeApprovalWorkspace $getWorkspace,
    ): Response {
        $filters = [
            'tab' => (string) $request->validated('tab', 'pending'),
            'search' => trim((string) $request->validated('search', '')),
            'date_from' => (string) $request->validated('date_from', ''),
            'date_to' => (string) $request->validated('date_to', ''),
        ];
        $workspace = $getWorkspace->execute($filters);
        $paginator = $workspace['submissions'];

        return Inertia::render('back-office/intake/approvals/Index', [
            'submissions' => [
                'data' => IntakeApprovalSubmissionResource::collection($paginator->getCollection())
                    ->resolve($request),
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
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.intake.approvals.index'),
            ],
        ]);
    }

    public function show(Request $request, LetterSubmission $submission): Response
    {
        Gate::authorize('viewApproval', $submission);
        $submission->load($this->detailRelations());

        return Inertia::render('back-office/intake/approvals/Show', [
            'submission' => (new IntakeApprovalSubmissionResource($submission))->resolve($request),
            'senderOrganizations' => SenderOrganization::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'address', 'contact']),
        ]);
    }

    /** @return list<string> */
    private function detailRelations(): array
    {
        return [
            'document',
            'reviews.createdBy',
            'latestReview.createdBy',
            'decisions.createdBy',
            'latestDecision.createdBy',
            'incomingLetter.senderOrganization',
        ];
    }
}
