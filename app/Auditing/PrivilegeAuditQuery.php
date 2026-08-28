<?php

namespace App\Auditing;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PrivilegeAuditQuery
{
    private const string AUDIT_TIMEZONE = 'Asia/Makassar';

    /**
     * @param  array{action: string, source: string, actor: string, target_type: string, target: string, date_from: string, date_to: string}  $filters
     * @return Builder<AuditLog>
     */
    public function build(array $filters): Builder
    {
        $query = AuditLog::query()
            ->whereIn('action', PrivilegeAuditCatalog::actions())
            ->whereIn('subject_type', PrivilegeAuditCatalog::targetTypes())
            ->with('actor:id,name,email');

        $query
            ->when($filters['action'] !== '', fn (Builder $query): Builder => $query->where('action', $filters['action']))
            ->when($filters['source'] !== '', fn (Builder $query): Builder => $query->where('metadata->source', $filters['source']))
            ->when($filters['target_type'] !== '', fn (Builder $query): Builder => $query->where('subject_type', $filters['target_type']));

        if ($filters['actor'] !== '') {
            $this->applyActorFilter($query, $filters['actor']);
        }

        if ($filters['target'] !== '') {
            $this->applyTargetFilter($query, $filters['target']);
        }

        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query;
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @return array{total: int, web: int, console: int}
     */
    public function summary(Builder $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'web' => (clone $query)->where('metadata->source', 'web')->count(),
            'console' => (clone $query)->where('metadata->source', 'console')->count(),
        ];
    }

    /** @param Builder<AuditLog> $query */
    private function applyActorFilter(Builder $query, string $search): void
    {
        $pattern = "%{$search}%";
        $normalizedSearch = mb_strtolower(trim($search));
        $matchesSystem = mb_strlen($normalizedSearch) >= 2
            && (str_contains('sistem', $normalizedSearch) || str_contains('system', $normalizedSearch));

        $query->where(function (Builder $query) use ($pattern, $matchesSystem): void {
            $query->whereHas('actor', function (Builder $actor) use ($pattern): void {
                $actor->where(function (Builder $actor) use ($pattern): void {
                    $actor->where('name', 'like', $pattern)
                        ->orWhere('email', 'like', $pattern);
                });
            });

            if ($matchesSystem) {
                $query->orWhereNull('actor_user_id');
            }
        });
    }

    /** @param Builder<AuditLog> $query */
    private function applyTargetFilter(Builder $query, string $search): void
    {
        $pattern = "%{$search}%";

        $query->where(function (Builder $query) use ($pattern, $search): void {
            $query
                ->orWhere(fn (Builder $target): Builder => $target
                    ->where('subject_type', 'user')
                    ->whereIn('subject_id', User::query()
                        ->select('id')
                        ->where(fn (Builder $user): Builder => $user
                            ->where('name', 'like', $pattern)
                            ->orWhere('email', 'like', $pattern))))
                ->orWhere(fn (Builder $target): Builder => $target
                    ->where('subject_type', 'role')
                    ->whereIn('subject_id', Role::query()
                        ->select('id')
                        ->where('name', 'like', $pattern)))
                ->orWhere(fn (Builder $target): Builder => $target
                    ->where('subject_type', 'permission')
                    ->whereIn('subject_id', Permission::query()
                        ->select('id')
                        ->where('name', 'like', $pattern)))
                ->orWhere('old_values->name', 'like', $pattern)
                ->orWhere('new_values->name', 'like', $pattern)
                ->orWhere('old_values->email', 'like', $pattern)
                ->orWhere('new_values->email', 'like', $pattern);

            if (ctype_digit($search)) {
                $query->orWhere('subject_id', (int) $search);
            }
        });
    }

    /** @param Builder<AuditLog> $query */
    private function applyDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        if ($dateFrom !== '') {
            $query->where(
                'created_at',
                '>=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateFrom, self::AUDIT_TIMEZONE)->utc(),
            );
        }

        if ($dateTo !== '') {
            $query->where(
                'created_at',
                '<=',
                CarbonImmutable::createFromFormat('!Y-m-d', $dateTo, self::AUDIT_TIMEZONE)->endOfDay()->utc(),
            );
        }
    }
}
