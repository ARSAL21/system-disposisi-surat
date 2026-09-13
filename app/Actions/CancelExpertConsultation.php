<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\ExpertConsultationStatus;
use App\Exceptions\ExpertConsultationStateConflict;
use App\ExpertConsultations\ExpertConsultationPositionResolver;
use App\Models\ExpertConsultation;
use App\Models\IncomingLetter;
use App\Models\LetterRoute;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class CancelExpertConsultation
{
    public function __construct(private readonly ExpertConsultationPositionResolver $positionResolver, private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, LetterRoute $route, ExpertConsultation $consultation, string $reason): void
    {
        DB::transaction(function () use ($actor, $route, $consultation, $reason): void {
            $lockedRoute = LetterRoute::query()->whereKey($route->getKey())->lockForUpdate()->firstOrFail();
            $letter = IncomingLetter::query()->whereKey($lockedRoute->incoming_letter_id)->lockForUpdate()->firstOrFail();
            $locked = ExpertConsultation::query()->whereKey($consultation->getKey())->where('letter_route_id', $lockedRoute->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== ExpertConsultationStatus::Pending) {
                throw ExpertConsultationStateConflict::stale();
            }
            $assignment = $this->positionResolver->lockMayorAssignmentForPosition($actor, $lockedRoute->recipient_position_id);
            $now = Date::now();
            $locked->status = ExpertConsultationStatus::Cancelled;
            $locked->cancelled_at = $now;
            $locked->cancelled_by_user_id = $actor->getKey();
            $locked->cancelled_by_position_assignment_id = $assignment->getKey();
            $locked->cancellation_reason = $reason;
            $locked->save();
            $this->recordAudit->execute($actor, AuditAction::ExpertConsultationCancelled, 'expert_consultation', $locked->getKey(), oldValues: ['status' => ExpertConsultationStatus::Pending->value], newValues: ['status' => ExpertConsultationStatus::Cancelled->value], metadata: ['incoming_letter_id' => $letter->getKey(), 'letter_route_id' => $lockedRoute->getKey()], actorPositionAssignment: $assignment);
        }, attempts: 3);
    }
}
