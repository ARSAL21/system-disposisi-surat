<?php

namespace App\Documents;

use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class DocumentArchiveQuery
{
    /**
     * @param  array{search: string, status: string, date_from: string, date_to: string}  $filters
     * @return Builder<IncomingLetter>
     */
    public function build(User $user, array $filters): Builder
    {
        $query = $this->authorized($user)
            ->with([
                'senderOrganization:id,name',
                'currentDocument',
            ])
            ->withCount('documents');

        if ($filters['search'] !== '') {
            $pattern = '%'.$filters['search'].'%';

            $query->where(function (Builder $search) use ($pattern): void {
                $search
                    ->where('agenda_number', 'like', $pattern)
                    ->orWhere('subject', 'like', $pattern)
                    ->orWhereHas('senderOrganization', fn (Builder $sender): Builder => $sender
                        ->where('name', 'like', $pattern))
                    ->orWhereHas('documents', fn (Builder $documents): Builder => $documents
                        ->where('original_filename', 'like', $pattern));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $this->applyReceivedDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    /**
     * Summary is intentionally calculated from the whole authorized scope and
     * never from the filtered collection query.
     *
     * @return array{total_letters: int, corrected_letters: int, total_versions: int, updated_this_month: int}
     */
    public function summary(User $user): array
    {
        $scope = $this->authorized($user);
        $letterIds = (clone $scope)->select('incoming_letters.id');
        [$monthStart, $monthEnd] = $this->officeMonthUtcBounds();

        return [
            'total_letters' => (clone $scope)->count(),
            'corrected_letters' => (clone $scope)->has('documents', '>', 1)->count(),
            'total_versions' => LetterDocument::query()
                ->whereIn('incoming_letter_id', clone $letterIds)
                ->count(),
            'updated_this_month' => LetterDocument::query()
                ->whereIn('incoming_letter_id', clone $letterIds)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->distinct()
                ->count('incoming_letter_id'),
        ];
    }

    /** @return Builder<IncomingLetter> */
    public function authorized(User $user): Builder
    {
        $query = IncomingLetter::query();

        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function (QueryBuilder $assignments) use ($user): void {
            $assignments
                ->selectRaw('1')
                ->from('position_assignments as document_archive_assignments')
                ->join('positions as document_archive_positions', 'document_archive_positions.id', '=', 'document_archive_assignments.position_id')
                ->join('position_levels as document_archive_levels', 'document_archive_levels.id', '=', 'document_archive_positions.position_level_id')
                ->leftJoin('organizational_units as document_archive_units', 'document_archive_units.id', '=', 'document_archive_positions.organizational_unit_id')
                ->where('document_archive_assignments.user_id', $user->getKey())
                ->whereNull('document_archive_assignments.ended_at')
                ->where('document_archive_positions.is_active', true)
                ->where('document_archive_levels.is_active', true)
                ->where(function (QueryBuilder $authority): void {
                    $authority
                        ->where('document_archive_levels.code', OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL)
                        ->orWhere(function (QueryBuilder $generalAffairs): void {
                            $generalAffairs
                                ->whereIn('document_archive_levels.code', [
                                    OrganizationCatalog::GENERAL_AFFAIRS_LEVEL,
                                    OrganizationCatalog::SECTION_HEAD_LEVEL,
                                ])
                                ->where('document_archive_units.code', OrganizationCatalog::GENERAL_AFFAIRS_UNIT)
                                ->where('document_archive_units.is_active', true);
                        });
                });
        });
    }

    /** @param Builder<IncomingLetter> $query */
    private function applyReceivedDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $timezone = (string) config('letter-activity.timezone');

        if ($dateFrom !== '') {
            $query->where(
                'received_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, $timezone)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'received_at',
                '<=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, $timezone)->endOfDay()->utc(),
            );
        }
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function officeMonthUtcBounds(): array
    {
        $now = CarbonImmutable::now((string) config('letter-activity.timezone'));

        return [
            $now->startOfMonth()->utc(),
            $now->endOfMonth()->utc(),
        ];
    }
}
