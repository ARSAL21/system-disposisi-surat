<?php

namespace App\Auditing;

use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Disposition;
use App\Models\DispositionFollowUp;
use App\Models\DispositionRecipient;
use App\Models\ExpertConsultation;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class LetterActivityQuery
{
    /**
     * @param  array{action: string, source: string, actor: string, letter: string, date_from: string, date_to: string}  $filters
     * @return Builder<AuditLog>
     */
    public function build(array $filters): Builder
    {
        $query = $this->scopedQuery()->with([
            'actor:id,name,account_type',
            'actorPositionAssignment:id,position_id',
            'actorPositionAssignment.position:id,position_level_id,organizational_unit_id,name',
            'actorPositionAssignment.position.organizationalUnit:id,name',
        ]);

        $query
            ->when($filters['action'] !== '', fn (Builder $query): Builder => $query
                ->where('action', $filters['action']))
            ->when($filters['actor'] !== '', fn (Builder $query): Builder => $query
                ->where('actor_user_id', (int) $filters['actor']));

        $this->applySourceFilter($query, $filters['source']);

        if ($filters['letter'] !== '') {
            $this->applyLetterFilter($query, $filters['letter']);
        }

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query;
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return array{total: int, received: int, awaiting_approval: int, registered: int, needs_follow_up: int}
     */
    public function summary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'received' => (clone $query)->whereIn('action', [
                AuditAction::ManualSubmissionCreated->value,
                AuditAction::SubmissionSubmitted->value,
                AuditAction::SubmissionResubmitted->value,
            ])->count(),
            'awaiting_approval' => (clone $query)
                ->whereIn('action', [
                    AuditAction::ManualSubmissionCreated->value,
                    AuditAction::SubmissionReadyForApproval->value,
                ])
                ->count(),
            'registered' => (clone $query)
                ->where('action', AuditAction::LetterRegistered->value)
                ->count(),
            'needs_follow_up' => (clone $query)->whereIn('action', [
                AuditAction::SubmissionRevisionRequested->value,
                AuditAction::SubmissionReturnedToStaff->value,
                AuditAction::SubmissionRejected->value,
            ])->count(),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    public function actorOptions(): array
    {
        $actorIds = $this->scopedQuery()
            ->whereNotNull('actor_user_id')
            ->select('actor_user_id');

        return array_values(User::query()
            ->whereIn('id', $actorIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'value' => (string) $user->getKey(),
                'label' => $user->name,
            ])
            ->all());
    }

    /** @return Builder<AuditLog> */
    private function scopedQuery(): Builder
    {
        return AuditLog::query()->where(function (Builder $scope): void {
            $scope
                ->where(function (Builder $submissions): void {
                    $submissions
                        ->where('subject_type', 'letter_submission')
                        ->whereIn('action', LetterActivityCatalog::submissionActions());
                })
                ->orWhere(function (Builder $letters): void {
                    $letters
                        ->where('subject_type', 'incoming_letter')
                        ->where('action', AuditAction::LetterRegistered->value);
                })
                ->orWhere(function (Builder $documents): void {
                    $documents
                        ->where('subject_type', 'letter_document')
                        ->where('action', AuditAction::DocumentVersionCreated->value);
                })
                ->orWhere(function (Builder $routes): void {
                    $routes
                        ->where('subject_type', 'letter_route')
                        ->where('action', AuditAction::LetterRouted->value);
                })
                ->orWhere(function (Builder $dispositions): void {
                    $dispositions
                        ->where('subject_type', 'disposition')
                        ->where('action', AuditAction::DispositionCreated->value);
                })
                ->orWhere(function (Builder $recipients): void {
                    $recipients
                        ->where('subject_type', 'disposition_recipient')
                        ->whereIn('action', [
                            AuditAction::DispositionStarted->value,
                            AuditAction::DispositionCompleted->value,
                        ]);
                })
                ->orWhere(function (Builder $followUps): void {
                    $followUps
                        ->where('subject_type', 'disposition_follow_up')
                        ->where('action', AuditAction::FollowUpAdded->value);
                })
                ->orWhere(function (Builder $completedLetters): void {
                    $completedLetters
                        ->where('subject_type', 'incoming_letter')
                        ->whereIn('action', [
                            AuditAction::LetterCompleted->value,
                            AuditAction::LetterResponseDossierOpened->value,
                            AuditAction::LetterResponseDocumentVersionCreated->value,
                            AuditAction::LetterResponseDocumentReturned->value,
                            AuditAction::LetterResponseMandateAuthorized->value,
                            AuditAction::LetterResponseDossierFinalized->value,
                            AuditAction::OutgoingLetterNumberAssigned->value,
                            AuditAction::OutgoingLetterDocumentVersionCreated->value,
                            AuditAction::OutgoingLetterDocumentReturned->value,
                            AuditAction::OutgoingLetterAdminVerified->value,
                            AuditAction::OutgoingLetterDelivered->value,
                            AuditAction::OutgoingLetterMandateWithdrawn->value,
                            AuditAction::LetterResponseDossierFulfilled->value,
                        ]);
                })
                ->orWhere(function (Builder $expertConsultations): void {
                    $expertConsultations
                        ->where('subject_type', 'expert_consultation')
                        ->whereIn('action', [
                            AuditAction::ExpertConsultationRequested->value,
                            AuditAction::ExpertConsultationReported->value,
                            AuditAction::ExpertConsultationCancelled->value,
                        ]);
                });
        });
    }

    /** @param Builder<AuditLog> $query */
    private function applySourceFilter(Builder $query, string $source): void
    {
        $accountType = match ($source) {
            LetterActivityCatalog::SOURCE_PUBLIC => AccountType::PublicAccount,
            LetterActivityCatalog::SOURCE_INTERNAL => AccountType::InternalAccount,
            default => null,
        };

        if ($accountType === null) {
            return;
        }

        $query->whereHas('actor', fn (Builder $actor): Builder => $actor
            ->where('account_type', $accountType->value));
    }

    /** @param Builder<AuditLog> $query */
    private function applyLetterFilter(Builder $query, string $search): void
    {
        $pattern = "%{$search}%";
        $submissionIds = $this->matchingSubmissionIds($pattern);
        $letterIds = $this->matchingIncomingLetterIds($pattern);
        $documentIds = LetterDocument::query()
            ->select('id')
            ->whereIn('incoming_letter_id', clone $letterIds);
        $routeIds = LetterRoute::query()
            ->select('id')
            ->whereIn('incoming_letter_id', clone $letterIds);
        $dispositionIds = Disposition::query()
            ->select('id')
            ->whereIn('incoming_letter_id', clone $letterIds);
        $recipientIds = DispositionRecipient::query()
            ->select('disposition_recipients.id')
            ->join('dispositions', 'dispositions.id', '=', 'disposition_recipients.disposition_id')
            ->whereIn('dispositions.incoming_letter_id', clone $letterIds);
        $followUpIds = DispositionFollowUp::query()
            ->select('disposition_follow_ups.id')
            ->join('disposition_recipients', 'disposition_recipients.id', '=', 'disposition_follow_ups.disposition_recipient_id')
            ->join('dispositions', 'dispositions.id', '=', 'disposition_recipients.disposition_id')
            ->whereIn('dispositions.incoming_letter_id', clone $letterIds);
        $expertConsultationIds = ExpertConsultation::query()
            ->select('id')
            ->whereIn('incoming_letter_id', clone $letterIds);

        $query->where(function (Builder $target) use ($submissionIds, $letterIds, $documentIds, $routeIds, $dispositionIds, $recipientIds, $followUpIds, $expertConsultationIds): void {
            $target
                ->where(function (Builder $submission) use ($submissionIds): void {
                    $submission
                        ->where('subject_type', 'letter_submission')
                        ->whereIn('subject_id', $submissionIds);
                })
                ->orWhere(function (Builder $letter) use ($letterIds): void {
                    $letter
                        ->where('subject_type', 'incoming_letter')
                        ->whereIn('subject_id', $letterIds);
                })
                ->orWhere(function (Builder $document) use ($documentIds): void {
                    $document
                        ->where('subject_type', 'letter_document')
                        ->whereIn('subject_id', $documentIds);
                })
                ->orWhere(function (Builder $route) use ($routeIds): void {
                    $route
                        ->where('subject_type', 'letter_route')
                        ->whereIn('subject_id', $routeIds);
                })
                ->orWhere(function (Builder $disposition) use ($dispositionIds): void {
                    $disposition
                        ->where('subject_type', 'disposition')
                        ->whereIn('subject_id', $dispositionIds);
                })
                ->orWhere(function (Builder $recipient) use ($recipientIds): void {
                    $recipient
                        ->where('subject_type', 'disposition_recipient')
                        ->whereIn('subject_id', $recipientIds);
                })
                ->orWhere(function (Builder $followUp) use ($followUpIds): void {
                    $followUp
                        ->where('subject_type', 'disposition_follow_up')
                        ->whereIn('subject_id', $followUpIds);
                })
                ->orWhere(function (Builder $consultation) use ($expertConsultationIds): void {
                    $consultation
                        ->where('subject_type', 'expert_consultation')
                        ->whereIn('subject_id', $expertConsultationIds);
                });
        });
    }

    /** @return Builder<LetterSubmission> */
    private function matchingSubmissionIds(string $pattern): Builder
    {
        return LetterSubmission::query()
            ->select('id')
            ->where(function (Builder $submission) use ($pattern): void {
                $submission
                    ->where('public_id', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhere('sender_organization_name', 'like', $pattern)
                    ->orWhere('external_letter_number', 'like', $pattern)
                    ->orWhereHas('incomingLetter', fn (Builder $letter): Builder => $letter
                        ->where('agenda_number', 'like', $pattern));
            });
    }

    /** @return Builder<IncomingLetter> */
    private function matchingIncomingLetterIds(string $pattern): Builder
    {
        return IncomingLetter::query()
            ->select('id')
            ->where(function (Builder $letter) use ($pattern): void {
                $letter
                    ->where('agenda_number', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhere('external_letter_number', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern))
                    ->orWhereHas('submission', fn (Builder $submission): Builder => $submission
                        ->where('public_id', 'like', $pattern));
            });
    }

    /** @param Builder<AuditLog> $query */
    private function applyDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $timezone = (string) config('letter-activity.timezone');

        if ($dateFrom !== '') {
            $query->where(
                'created_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'created_at',
                '<=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->utc(),
            );
        }
    }
}
