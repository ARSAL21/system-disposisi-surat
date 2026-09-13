<?php

namespace App\ExpertConsultations;

use App\Enums\ExpertConsultationStatus;
use App\Exceptions\ExpertConsultationStateConflict;
use App\Models\ExpertConsultation;
use App\Models\LetterRoute;

final class ExpertConsultationForwardingGuard
{
    public function ensureNoPendingForLockedRoute(LetterRoute $route): void
    {
        $pending = ExpertConsultation::query()
            ->where('letter_route_id', $route->getKey())
            ->where('status', ExpertConsultationStatus::Pending->value)
            ->orderBy('id')
            ->lockForUpdate()
            ->exists();
        if ($pending) {
            throw ExpertConsultationStateConflict::pending();
        }
    }
}
