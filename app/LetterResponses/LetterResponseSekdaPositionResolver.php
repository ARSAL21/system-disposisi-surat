<?php

namespace App\LetterResponses;

use App\Exceptions\LetterResponseStateConflict;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\LetterRoute;
use App\Models\Position;
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves the Sekda who owns a response dossier.
 *
 * A letter may be routed straight to Sekda, or formally pass through Wali Kota
 * first. In both cases, response drafting, mandate authorization, and dossier
 * finalization remain the responsibility of the same SEKDA Position.
 */
final class LetterResponseSekdaPositionResolver
{
    public function positionId(IncomingLetter $letter): ?int
    {
        return $this->resolve($letter, false);
    }

    public function lockPositionId(IncomingLetter $letter): int
    {
        return $this->resolve($letter, true)
            ?? throw LetterResponseStateConflict::staleDossier();
    }

    private function resolve(IncomingLetter $letter, bool $lock): ?int
    {
        $route = $this->routeQuery($letter, $lock)->first();

        if (! $route instanceof LetterRoute) {
            return null;
        }

        if ($this->isRegionalSecretaryPosition((int) $route->recipient_position_id, $lock)) {
            return (int) $route->recipient_position_id;
        }

        if (! $this->isMayorPosition((int) $route->recipient_position_id, $lock)) {
            return null;
        }

        $sourceDisposition = Disposition::query()
            ->where('source_route_id', $route->getKey())
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate())
            ->first();

        if (! $sourceDisposition instanceof Disposition) {
            return null;
        }

        $recipients = DispositionRecipient::query()
            ->where('disposition_id', $sourceDisposition->getKey())
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate())
            ->orderBy('id')
            ->limit(2)
            ->get(['id', 'recipient_position_id']);

        if ($recipients->count() !== 1) {
            return null;
        }

        $positionId = (int) $recipients->firstOrFail()->recipient_position_id;

        return $this->isSekdaPosition($positionId, $lock) ? $positionId : null;
    }

    /** @return Builder<LetterRoute> */
    private function routeQuery(IncomingLetter $letter, bool $lock): Builder
    {
        return LetterRoute::query()
            ->where('incoming_letter_id', $letter->getKey())
            ->orderBy('id')
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate());
    }

    private function isMayorPosition(int $positionId, bool $lock): bool
    {
        return $this->positionQuery($positionId, OrganizationCatalog::MAYOR_POSITION, OrganizationCatalog::MAYOR_LEVEL, $lock)
            ->first() instanceof Position;
    }

    private function isSekdaPosition(int $positionId, bool $lock): bool
    {
        return $this->positionQuery(
            $positionId,
            OrganizationCatalog::REGIONAL_SECRETARY_POSITION,
            OrganizationCatalog::REGIONAL_SECRETARY_LEVEL,
            $lock,
        )->first() instanceof Position;
    }

    private function isRegionalSecretaryPosition(int $positionId, bool $lock): bool
    {
        return Position::query()
            ->whereKey($positionId)
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $positionLevel): Builder => $positionLevel
                ->where('code', OrganizationCatalog::REGIONAL_SECRETARY_LEVEL)
                ->where('is_active', true))
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate())
            ->first() instanceof Position;
    }

    /** @return Builder<Position> */
    private function positionQuery(int $positionId, string $code, string $level, bool $lock): Builder
    {
        return Position::query()
            ->whereKey($positionId)
            ->where('code', $code)
            ->where('is_active', true)
            ->whereHas('positionLevel', fn (Builder $positionLevel): Builder => $positionLevel
                ->where('code', $level)
                ->where('is_active', true))
            ->when($lock, fn (Builder $query): Builder => $query->lockForUpdate());
    }
}
