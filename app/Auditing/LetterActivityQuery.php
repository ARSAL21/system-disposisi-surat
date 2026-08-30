<?php

namespace App\Auditing;

use App\Enums\AccountType;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
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
                AuditAction::SubmissionSubmitted->value,
                AuditAction::SubmissionResubmitted->value,
            ])->count(),
            'awaiting_approval' => (clone $query)
                ->where('action', AuditAction::SubmissionReadyForApproval->value)
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

        $query->where(function (Builder $target) use ($submissionIds, $letterIds, $documentIds): void {
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
