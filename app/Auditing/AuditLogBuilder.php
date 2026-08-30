<?php

namespace App\Auditing;

use App\Exceptions\AuditLogMutationDenied;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\AuditLog
 *
 * @extends Builder<TModel>
 */
final class AuditLogBuilder extends Builder
{
    /** @param array<string, mixed> $values */
    public function update(array $values)
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<int, array<string, mixed>> $values */
    public function upsert(array $values, $uniqueBy, $update = null)
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $attributes */
    public function updateOrCreate(array $attributes, Closure|array $values = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $attributes */
    public function incrementOrCreate(
        array $attributes,
        string $column = 'count',
        $default = 1,
        $step = 1,
        array $extra = [],
    ) {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function touch($column = null)
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, float|int|numeric-string> $columns */
    public function incrementEach(array $columns, array $extra = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, float|int|numeric-string> $columns */
    public function decrementEach(array $columns, array $extra = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function delete()
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function forceDelete()
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $attributes */
    public function updateOrInsert(array $attributes, array|callable $values = [])
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $values */
    public function updateFrom(array $values)
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function truncate()
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }
}
