<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\LetterSubmission;
use App\Models\User;
use App\Services\IntakeApprovalPositionAssignmentResolver;
use App\Services\IntakePositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

class LetterSubmissionPolicy
{
    public function __construct(
        private readonly IntakePositionAssignmentResolver $intakePositionAssignmentResolver,
        private readonly IntakeApprovalPositionAssignmentResolver $intakeApprovalPositionAssignmentResolver,
    ) {}

    public function viewAny(User $user): Response
    {
        return $this->isEligiblePublicUser($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response
    {
        return $this->isEligiblePublicUser($user)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function view(User $user, LetterSubmission $submission): Response
    {
        return $this->ownsOnlineSubmission($user, $submission)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function update(User $user, LetterSubmission $submission): Response
    {
        return $this->view($user, $submission);
    }

    public function delete(User $user, LetterSubmission $submission): Response
    {
        return $this->view($user, $submission);
    }

    public function replaceDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->view($user, $submission);
    }

    public function downloadDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->view($user, $submission);
    }

    public function submit(User $user, LetterSubmission $submission): Response
    {
        return $this->view($user, $submission);
    }

    public function viewAnyIntake(User $user): bool
    {
        return $this->isEligibleInternalUser($user)
            && $user->can(PermissionName::ViewIntake->value)
            && $this->intakePositionAssignmentResolver->hasActiveAssignment($user);
    }

    public function viewIntake(User $user, LetterSubmission $submission): Response
    {
        return $this->viewAnyIntake($user)
            && $submission->status !== SubmissionStatus::Draft
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function screenIntake(User $user, LetterSubmission $submission): Response
    {
        if (! $this->viewIntake($user, $submission)->allowed()) {
            return Response::denyAsNotFound();
        }

        return $user->can(PermissionName::ScreenIntake->value)
            ? Response::allow()
            : Response::deny('You do not have permission to screen intake submissions.');
    }

    public function downloadIntakeDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->viewIntake($user, $submission);
    }

    public function viewAnyApproval(User $user): bool
    {
        return $this->isEligibleInternalUser($user)
            && $user->can(PermissionName::DecideIntake->value)
            && $this->intakeApprovalPositionAssignmentResolver->hasActiveAssignment($user);
    }

    public function viewApproval(User $user, LetterSubmission $submission): Response
    {
        return $this->viewAnyApproval($user)
            && in_array($submission->status, [
                SubmissionStatus::ReadyForApproval,
                SubmissionStatus::InternalRevisionRequired,
                SubmissionStatus::Registered,
                SubmissionStatus::Rejected,
            ], true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function decideIntake(User $user, LetterSubmission $submission): Response
    {
        return $this->viewApproval($user, $submission);
    }

    public function downloadApprovalDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->viewApproval($user, $submission);
    }

    private function isEligiblePublicUser(User $user): bool
    {
        return $user->isPublicAccount()
            && $user->is_active
            && $user->hasVerifiedEmail();
    }

    private function ownsOnlineSubmission(User $user, LetterSubmission $submission): bool
    {
        return $this->isEligiblePublicUser($user)
            && $submission->source === SubmissionSource::Online
            && $submission->submitted_by_user_id === $user->getKey();
    }

    private function isEligibleInternalUser(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->is_active
            && $user->hasVerifiedEmail();
    }
}
