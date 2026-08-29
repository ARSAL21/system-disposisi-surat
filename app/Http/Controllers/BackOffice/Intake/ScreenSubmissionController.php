<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\ScreenSubmission;
use App\Enums\SubmissionReviewOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\ScreenSubmissionRequest;
use App\Http\Resources\IntakeSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ScreenSubmissionController extends Controller
{
    public function __invoke(
        ScreenSubmissionRequest $request,
        LetterSubmission $submission,
        ScreenSubmission $screenSubmission,
    ): IntakeSubmissionResource|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        /** @var list<array{id: string, checked: bool}> $checklist */
        $checklist = $request->validated('checklist');
        $outcome = SubmissionReviewOutcome::from($request->validated('outcome'));
        $submission = $screenSubmission->execute(
            actor: $actor,
            submission: $submission,
            outcome: $outcome,
            checklist: $checklist,
            note: $request->validated('note'),
        );
        $submission->load(['document', 'reviews', 'decisions', 'latestDecision']);

        if ($request->expectsJson()) {
            return new IntakeSubmissionResource($submission);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $outcome === SubmissionReviewOutcome::RevisionRequired
                ? __('Permintaan koreksi telah dikirim kepada pengirim.')
                : __('Submission telah diajukan kepada Kepala Bagian Umum.'),
        ]);

        return to_route('back-office.intake.submissions.show', $submission);
    }
}
