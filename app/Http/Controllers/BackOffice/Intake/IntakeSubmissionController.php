<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\GetIntakeSubmissionWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\ListIntakeSubmissionsRequest;
use App\Http\Resources\IntakeSubmissionResource;
use App\Models\LetterSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IntakeSubmissionController extends Controller
{
    public function index(
        ListIntakeSubmissionsRequest $request,
        GetIntakeSubmissionWorkspace $getWorkspace,
    ): Response {
        $filters = [
            'search' => trim((string) $request->validated('search', '')),
            'status' => (string) $request->validated('status', 'action_required'),
            'source' => (string) $request->validated('source', 'all'),
            'date_from' => (string) $request->validated('date_from', ''),
            'date_to' => (string) $request->validated('date_to', ''),
        ];
        $workspace = $getWorkspace->execute($filters);

        return Inertia::render('back-office/intake/submissions/Index', [
            'submissions' => IntakeSubmissionResource::collection($workspace['submissions'])
                ->response()
                ->getData(true),
            'summary' => $workspace['summary'],
            'filters' => $filters,
            'routes' => [
                'index' => route('back-office.intake.submissions.index'),
            ],
        ]);
    }

    public function show(Request $request, LetterSubmission $submission): Response
    {
        Gate::authorize('viewIntake', $submission);

        $submission->load(['document', 'reviews', 'decisions', 'latestDecision']);

        return Inertia::render('back-office/intake/submissions/Show', [
            'submission' => (new IntakeSubmissionResource($submission))->resolve($request),
        ]);
    }
}
