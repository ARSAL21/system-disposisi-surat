<?php

namespace App\Reporting;

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReportScopeResolver
{
    public function resolve(User $user): ?ReportScope
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return null;
        }

        /** @var Collection<int, PositionAssignment> $assignments */
        $assignments = PositionAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->whereHas('position', fn (Builder $position): Builder => $position
                ->where('is_active', true)
                ->whereHas('positionLevel', fn (Builder $level): Builder => $level
                    ->where('is_active', true)
                    ->whereIn('code', [
                        ...OrganizationCatalog::executiveLevelCodes(),
                        OrganizationCatalog::ASSISTANT_LEVEL,
                        OrganizationCatalog::SECTION_HEAD_LEVEL,
                    ])))
            ->with([
                'position.positionLevel:id,code',
                'position.organizationalUnit:id,code,name,is_active',
            ])
            ->get();

        $positions = $assignments
            ->map(fn (PositionAssignment $assignment): Position => $assignment->position)
            ->filter(fn (Position $position): bool => $position->organizationalUnit === null
                || $position->organizationalUnit->is_active)
            ->unique('id')
            ->values();
        $executiveIds = array_values($positions
            ->filter(fn (Position $position): bool => in_array($position->positionLevel->code, OrganizationCatalog::executiveLevelCodes(), true))
            ->map(fn (Position $position): int => (int) $position->getKey())
            ->all());
        $assistantIds = $this->idsAtLevel($positions, OrganizationCatalog::ASSISTANT_LEVEL);
        $sectionHeadIds = $this->idsAtLevel($positions, OrganizationCatalog::SECTION_HEAD_LEVEL);
        $hasGeneralAffairsHead = $positions->contains(fn (Position $position): bool => $position->positionLevel->code === OrganizationCatalog::SECTION_HEAD_LEVEL
            && $position->organizationalUnit?->code === OrganizationCatalog::GENERAL_AFFAIRS_UNIT);

        if ($executiveIds !== []) {
            return new ReportScope(
                mode: 'CITYWIDE',
                label: 'Cakupan seluruh kota',
                description: 'Seluruh surat dan cabang disposisi berada dalam cakupan pimpinan eksekutif aktif.',
                hasGlobalAggregates: true,
                hasGlobalDetails: true,
                executivePositionIds: $executiveIds,
                assistantPositionIds: $assistantIds,
                sectionHeadPositionIds: $sectionHeadIds,
            );
        }

        if ($hasGeneralAffairsHead) {
            return new ReportScope(
                mode: 'GENERAL_AFFAIRS_GLOBAL',
                label: 'Agregat operasional Bagian Umum',
                description: 'Agregat bersifat global; daftar dan detail dibatasi pada cabang jabatan aktif Anda.',
                hasGlobalAggregates: true,
                hasGlobalDetails: false,
                executivePositionIds: [],
                assistantPositionIds: $assistantIds,
                sectionHeadPositionIds: $sectionHeadIds,
            );
        }

        if ($assistantIds !== []) {
            return new ReportScope(
                mode: 'ASSISTANT_SUBTREE',
                label: 'Subtree Asisten aktif',
                description: 'Surat dan cabang Kepala Bagian langsung di bawah disposisi Asisten aktif Anda.',
                hasGlobalAggregates: false,
                hasGlobalDetails: false,
                executivePositionIds: [],
                assistantPositionIds: $assistantIds,
                sectionHeadPositionIds: $sectionHeadIds,
            );
        }

        if ($sectionHeadIds !== []) {
            return new ReportScope(
                mode: 'SECTION_HEAD_BRANCHES',
                label: 'Cabang Kepala Bagian aktif',
                description: 'Hanya surat dan cabang disposisi milik jabatan aktif Anda.',
                hasGlobalAggregates: false,
                hasGlobalDetails: false,
                executivePositionIds: [],
                assistantPositionIds: [],
                sectionHeadPositionIds: $sectionHeadIds,
            );
        }

        return null;
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return list<int>
     */
    private function idsAtLevel(Collection $positions, string $level): array
    {
        return array_values($positions
            ->filter(fn (Position $position): bool => $position->positionLevel->code === $level)
            ->map(fn (Position $position): int => (int) $position->getKey())
            ->all());
    }
}
