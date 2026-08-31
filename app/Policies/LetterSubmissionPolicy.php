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

    public function viewDocument(User $user, LetterSubmission $submission): Response
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
        return $this->authorizeIntakeDocumentAccess($user, $submission);
    }

    public function screenIntake(User $user, LetterSubmission $submission): Response
    {
        if (! $this->authorizeIntakeDocumentAccess($user, $submission)->allowed()) {
            return Response::denyAsNotFound();
        }

        return $user->can(PermissionName::ScreenIntake->value)
            ? Response::allow()
            : Response::deny('You do not have permission to screen intake submissions.');
    }

    public function viewIntakeDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeIntakeDocumentAccess($user, $submission);
    }

    public function downloadIntakeDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeIntakeDocumentAccess($user, $submission);
    }

    public function viewAnyApproval(User $user): bool
    {
        return $this->isEligibleInternalUser($user)
            && $user->can(PermissionName::DecideIntake->value)
            && $this->intakeApprovalPositionAssignmentResolver->hasActiveAssignment($user);
    }

    public function viewApproval(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeApprovalDocumentAccess($user, $submission);
    }

    public function decideIntake(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeApprovalDocumentAccess($user, $submission);
    }

    public function viewApprovalDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeApprovalDocumentAccess($user, $submission);
    }

    public function downloadApprovalDocument(User $user, LetterSubmission $submission): Response
    {
        return $this->authorizeApprovalDocumentAccess($user, $submission);
    }

    private function authorizeIntakeDocumentAccess(User $user, LetterSubmission $submission): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::ViewIntake->value)) {
            return Response::deny('You do not have permission to view intake submissions.');
        }

        if (! $this->intakePositionAssignmentResolver->hasActiveAssignment($user)) {
            return Response::denyAsNotFound();
        }

        if ($submission->status === SubmissionStatus::Draft) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    private function authorizeApprovalDocumentAccess(User $user, LetterSubmission $submission): Response
    {
        if (! $this->isEligibleInternalUser($user)) {
            return Response::denyAsNotFound();
        }

        if (! $user->can(PermissionName::DecideIntake->value)) {
            return Response::deny('You do not have permission to decide intake submissions.');
        }

        if (! $this->intakeApprovalPositionAssignmentResolver->hasActiveAssignment($user)) {
            return Response::denyAsNotFound();
        }

        if (! in_array($submission->status, [
            SubmissionStatus::ReadyForApproval,
            SubmissionStatus::InternalRevisionRequired,
            SubmissionStatus::Registered,
            SubmissionStatus::Rejected,
        ], true)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
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
            && (int) $submission->submitted_by_user_id === (int) $user->getKey();
    }

    private function isEligibleInternalUser(User $user): bool
    {
        return $user->isInternalAccount()
            && $user->is_active
            && $user->hasVerifiedEmail();
    }
}
