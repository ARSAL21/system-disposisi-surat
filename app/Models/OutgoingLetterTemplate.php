<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

/**
 * @property int $id
 * @property string $public_id
 * @property int $organizational_unit_id
 * @property string $code
 * @property string $name
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read OrganizationalUnit $organizationalUnit
 * @property-read Collection<int, OutgoingLetterTemplateVersion> $versions
 */
class OutgoingLetterTemplate extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $template) => $template->public_id ??= (string) Str::ulid());
        static::updating(function (self $template): void {
            if (array_diff(array_keys($template->getDirty()), ['is_active', 'updated_at']) !== []) {
                throw new LogicException('Template surat tidak dapat diubah selain status aktifnya.');
            }
        });
        static::deleting(fn () => throw new LogicException('Template surat tidak dapat dihapus.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return HasMany<OutgoingLetterTemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(OutgoingLetterTemplateVersion::class)->orderBy('version_number');
    }

    /** @return HasMany<OutgoingLetterTemplateVersion, $this> */
    public function outgoingLetterTemplateVersions(): HasMany
    {
        return $this->versions();
    }
}
