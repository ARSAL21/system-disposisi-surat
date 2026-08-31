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
    public function update(array $values): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<int, array<string, mixed>>  $values
     * @param  string|array<int, string>  $uniqueBy
     * @param  array<int, string>|null  $update
     */
    public function upsert(array $values, $uniqueBy, $update = null): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  Closure|array<string, mixed>  $values
     */
    public function updateOrCreate(array $attributes, Closure|array $values = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $extra
     */
    public function incrementOrCreate(
        array $attributes,
        string $column = 'count',
        $default = 1,
        $step = 1,
        array $extra = [],
    ): never {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param string|array<int, string>|null $column */
    public function touch($column = null): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $extra */
    public function increment($column, $amount = 1, array $extra = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $extra */
    public function decrement($column, $amount = 1, array $extra = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function incrementEach(array $columns, array $extra = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<string, float|int|numeric-string>  $columns
     * @param  array<string, mixed>  $extra
     */
    public function decrementEach(array $columns, array $extra = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function delete(): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function forceDelete(): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>|callable(): array<string, mixed>  $values
     */
    public function updateOrInsert(array $attributes, array|callable $values = []): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    /** @param array<string, mixed> $values */
    public function updateFrom(array $values): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }

    public function truncate(): never
    {
        throw AuditLogMutationDenied::forAppendOnlyRecord();
    }
}
