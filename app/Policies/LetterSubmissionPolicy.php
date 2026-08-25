<?php

namespace App\Policies;

use App\Enums\SubmissionSource;
use App\Models\LetterSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LetterSubmissionPolicy
{
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
}
