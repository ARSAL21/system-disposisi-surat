<?php

namespace App\Http\Controllers\BackOffice\Intake;

use App\Actions\RegisterIncomingLetter;
use App\Actions\RejectSubmission;
use App\Actions\ReturnSubmissionToIntakeStaff;
use App\Enums\SubmissionDecisionOutcome;
use App\Http\Controllers\Controller;
use App\Http\Requests\BackOffice\Intake\DecideSubmissionRequest;
use App\Http\Resources\IntakeApprovalSubmissionResource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SubmissionDecisionController extends Controller
{
    public function __invoke(
        DecideSubmissionRequest $request,
        LetterSubmission $submission,
        ReturnSubmissionToIntakeStaff $returnToStaff,
        RejectSubmission $rejectSubmission,
        RegisterIncomingLetter $registerIncomingLetter,
    ): IntakeApprovalSubmissionResource|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $outcome = $request->outcome();
        $note = $request->decisionNote();

        match ($outcome) {
            SubmissionDecisionOutcome::InternalRevisionRequired => $returnToStaff->execute(
                $actor,
                $submission,
                (string) $note,
            ),
            SubmissionDecisionOutcome::Rejected => $rejectSubmission->execute(
                $actor,
                $submission,
                (string) $note,
            ),
            SubmissionDecisionOutcome::Registered => $registerIncomingLetter->execute(
                $actor,
                $submission,
                $request->registrationPayload(),
            ),
        };

        $submission->refresh()->load([
            'document',
            'reviews.createdBy',
            'latestReview.createdBy',
            'decisions.createdBy',
            'latestDecision.createdBy',
            'incomingLetter.senderOrganization',
            'incomingLetter.initialDocument',
        ]);

        if ($request->expectsJson()) {
            return new IntakeApprovalSubmissionResource($submission);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => match ($outcome) {
                SubmissionDecisionOutcome::InternalRevisionRequired => __('Surat dikembalikan kepada petugas untuk diperbaiki.'),
                SubmissionDecisionOutcome::Rejected => __('Pengajuan surat telah ditolak.'),
                SubmissionDecisionOutcome::Registered => __('Surat berhasil diregistrasikan secara resmi.'),
            },
        ]);

        return to_route('back-office.intake.approvals.show', $submission);
    }
}
