<?php

namespace App\Reporting;

use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

final class ReportLetterQuery
{
    public function __construct(
        private readonly ReportScopeResolver $scopeResolver,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return Builder<IncomingLetter>
     */
    public function list(User $user, array $filters): Builder
    {
        return $this->applyFilters($this->detailScope($user), $filters)
            ->with([
                'submission:id,source',
                'senderOrganization:id,name',
            ])
            ->select('incoming_letters.*')
            ->selectSub($this->processingStartedSubquery(), 'processing_started_at')
            ->selectSub($this->terminalCompletionSubquery(), 'report_completed_at')
            ->selectSub($this->terminalCountSubquery(), 'report_branch_total')
            ->selectSub(
                $this->terminalCountSubquery(DispositionRecipientStatus::Pending->value),
                'report_branch_pending',
            )
            ->selectSub(
                $this->terminalCountSubquery(DispositionRecipientStatus::InProgress->value),
                'report_branch_in_progress',
            )
            ->selectSub(
                $this->terminalCountSubquery(DispositionRecipientStatus::Completed->value),
                'report_branch_completed',
            )
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    /**
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return Builder<IncomingLetter>
     */
    public function aggregate(User $user, array $filters): Builder
    {
        return $this->applyFilters($this->aggregateScope($user), $filters);
    }

    /** @return Builder<IncomingLetter> */
    public function aggregateScope(User $user): Builder
    {
        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            return IncomingLetter::query()->whereRaw('1 = 0');
        }

        return $scope->hasGlobalAggregates
            ? IncomingLetter::query()
            : $this->applyDetailVisibility(IncomingLetter::query(), $scope);
    }

    /** @return Builder<IncomingLetter> */
    public function detailScope(User $user): Builder
    {
        $scope = $this->scopeResolver->resolve($user);

        if ($scope === null) {
            return IncomingLetter::query()->whereRaw('1 = 0');
        }

        return $scope->hasGlobalDetails
            ? IncomingLetter::query()
            : $this->applyDetailVisibility(IncomingLetter::query(), $scope);
    }

    public function isDetailVisible(User $user, IncomingLetter $incomingLetter): bool
    {
        return $this->detailScope($user)
            ->whereKey($incomingLetter->getKey())
            ->exists();
    }

    /**
     * @param  Builder<IncomingLetter>  $query
     * @param  array{date_from: string, date_to: string, source: string, event: string, status: string, search: string}  $filters
     * @return Builder<IncomingLetter>
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['source'] !== '') {
            $query->whereHas('submission', fn (Builder $submission): Builder => $submission
                ->where('source', $filters['source']));
        }

        if ($filters['status'] !== '') {
            $query->where('incoming_letters.status', $filters['status']);
        }

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';

            $query->where(function (Builder $search) use ($pattern): void {
                $search
                    ->where('incoming_letters.agenda_number', 'like', $pattern)
                    ->orWhere('incoming_letters.subject', 'like', $pattern)
                    ->orWhere('incoming_letters.external_letter_number', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern));
            });
        }

        [$from, $to] = $this->officeUtcBounds($filters['date_from'], $filters['date_to']);

        if ($filters['event'] === 'PROCESSING_STARTED') {
            return $query->whereHas('dispositions', fn (Builder $disposition): Builder => $disposition
                ->whereNotNull('source_route_id')
                ->whereBetween('created_at', [$from, $to]));
        }

        if ($filters['event'] === 'COMPLETED') {
            return $query
                ->where('incoming_letters.status', IncomingLetterStatus::Completed->value)
                ->whereIn('incoming_letters.id', $this->completedLetterIdsBetween($from, $to));
        }

        return $query->whereBetween('incoming_letters.received_at', [$from, $to]);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function officeUtcBounds(string $dateFrom, string $dateTo): array
    {
        $timezone = (string) config('letter-activity.timezone');

        return [
            CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->utc(),
        ];
    }

    public function processingStartedSubquery(): QueryBuilder
    {
        return DB::table('dispositions as report_initial_dispositions')
            ->selectRaw('MIN(report_initial_dispositions.created_at)')
            ->whereColumn('report_initial_dispositions.incoming_letter_id', 'incoming_letters.id')
            ->whereNotNull('report_initial_dispositions.source_route_id');
    }

    public function terminalCompletionSubquery(): QueryBuilder
    {
        return $this->terminalRecipientsSubquery()
            ->selectRaw('MAX(report_terminal_recipients.completed_at)');
    }

    private function terminalCountSubquery(?string $status = null): QueryBuilder
    {
        $query = $this->terminalRecipientsSubquery()->selectRaw('COUNT(*)');

        return $status === null
            ? $query
            : $query->where('report_terminal_recipients.status', $status);
    }

    private function terminalRecipientsSubquery(): QueryBuilder
    {
        return DB::table('disposition_recipients as report_terminal_recipients')
            ->join('dispositions as report_terminal_dispositions', 'report_terminal_dispositions.id', '=', 'report_terminal_recipients.disposition_id')
            ->join('positions as report_terminal_positions', 'report_terminal_positions.id', '=', 'report_terminal_recipients.recipient_position_id')
            ->join('position_levels as report_terminal_levels', 'report_terminal_levels.id', '=', 'report_terminal_positions.position_level_id')
            ->whereColumn('report_terminal_dispositions.incoming_letter_id', 'incoming_letters.id')
            ->where('report_terminal_levels.code', OrganizationCatalog::SECTION_HEAD_LEVEL);
    }

    private function completedLetterIdsBetween(CarbonImmutable $from, CarbonImmutable $to): QueryBuilder
    {
        return DB::table('disposition_recipients as report_completed_recipients')
            ->join('dispositions as report_completed_dispositions', 'report_completed_dispositions.id', '=', 'report_completed_recipients.disposition_id')
            ->join('positions as report_completed_positions', 'report_completed_positions.id', '=', 'report_completed_recipients.recipient_position_id')
            ->join('position_levels as report_completed_levels', 'report_completed_levels.id', '=', 'report_completed_positions.position_level_id')
            ->where('report_completed_levels.code', OrganizationCatalog::SECTION_HEAD_LEVEL)
            ->groupBy('report_completed_dispositions.incoming_letter_id')
            ->havingRaw('COUNT(*) > 0')
            ->havingRaw('SUM(CASE WHEN report_completed_recipients.status <> ? THEN 1 ELSE 0 END) = 0', [
                DispositionRecipientStatus::Completed->value,
            ])
            ->havingRaw('MAX(report_completed_recipients.completed_at) BETWEEN ? AND ?', [$from, $to])
            ->select('report_completed_dispositions.incoming_letter_id');
    }

    /**
     * @param  Builder<IncomingLetter>  $query
     * @return Builder<IncomingLetter>
     */
    private function applyDetailVisibility(Builder $query, ReportScope $scope): Builder
    {
        return $query->where(function (Builder $visibility) use ($scope): void {
            if ($scope->assistantPositionIds !== []) {
                $visibility->whereHas('dispositions', fn (Builder $disposition): Builder => $disposition
                    ->whereNotNull('source_route_id')
                    ->whereHas('recipients', fn (Builder $recipient): Builder => $recipient
                        ->whereIn('recipient_position_id', $scope->assistantPositionIds)));
            }

            if ($scope->sectionHeadPositionIds !== []) {
                $sectionVisibility = fn (Builder $recipient): Builder => $recipient
                    ->whereIn('recipient_position_id', $scope->sectionHeadPositionIds)
                    ->whereHas('recipientPosition.positionLevel', fn (Builder $level): Builder => $level
                        ->where('code', OrganizationCatalog::SECTION_HEAD_LEVEL));

                if ($scope->assistantPositionIds === []) {
                    $visibility->whereHas('dispositions.recipients', $sectionVisibility);
                } else {
                    $visibility->orWhereHas('dispositions.recipients', $sectionVisibility);
                }
            }

            if ($scope->assistantPositionIds === [] && $scope->sectionHeadPositionIds === []) {
                $visibility->whereRaw('1 = 0');
            }
        });
    }
}
