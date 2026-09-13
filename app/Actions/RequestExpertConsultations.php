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
use App\Organization\OrganizationCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RequestExpertConsultations
{
    public function __construct(
        private readonly ExpertConsultationPositionResolver $positionResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    /**
     * @param  list<int>  $expertPositionIds
     * @return Collection<int, ExpertConsultation>
     */
    public function execute(User $actor, LetterRoute $route, array $expertPositionIds, ?string $requestNote): Collection
    {
        return DB::transaction(function () use ($actor, $route, $expertPositionIds, $requestNote): Collection {
            $lockedRoute = LetterRoute::query()->whereKey($route->getKey())->lockForUpdate()->firstOrFail();
            $this->assertMayorRoute($lockedRoute);
            $letter = IncomingLetter::query()->whereKey($lockedRoute->incoming_letter_id)->lockForUpdate()->firstOrFail();
            $existing = ExpertConsultation::query()->where('letter_route_id', $lockedRoute->getKey())->orderBy('id')->lockForUpdate()->get();
            $assignment = $this->positionResolver->lockMayorAssignmentForPosition($actor, $lockedRoute->recipient_position_id);
            $experts = $this->positionResolver->lockEligibleExperts($expertPositionIds, $lockedRoute->recipient_position_id);

            if ($existing->whereIn('expert_position_id', $experts->modelKeys())->where('status', ExpertConsultationStatus::Pending)->isNotEmpty()) {
                throw ValidationException::withMessages(['expert_position_ids' => 'Salah satu Staf Ahli tersebut masih memiliki tugas telaah aktif untuk surat ini.']);
            }

            $now = Date::now();
            $created = new Collection;
            foreach ($experts as $expert) {
                $consultation = new ExpertConsultation;
                $consultation->incoming_letter_id = $letter->getKey();
                $consultation->letter_route_id = $lockedRoute->getKey();
                $consultation->expert_position_id = $expert->getKey();
                $consultation->requested_by_user_id = $actor->getKey();
                $consultation->requested_by_position_assignment_id = $assignment->getKey();
                $consultation->status = ExpertConsultationStatus::Pending;
                $consultation->request_note = $requestNote;
                $consultation->requested_at = $now;
                $consultation->save();
                $this->recordAudit->execute(
                    actor: $actor,
                    action: AuditAction::ExpertConsultationRequested,
                    subjectType: 'expert_consultation',
                    subjectId: $consultation->getKey(),
                    newValues: ['status' => $consultation->status->value],
                    metadata: [
                        'incoming_letter_id' => $letter->getKey(),
                        'letter_route_id' => $lockedRoute->getKey(),
                        'expert_position_id' => $expert->getKey(),
                        'relationship_rule' => 'MAYOR_EXPERT_ACCOUNTABILITY',
                    ],
                    actorPositionAssignment: $assignment,
                );
                $created->push($consultation);
            }

            return $created;
        }, attempts: 3);
    }

    private function assertMayorRoute(LetterRoute $route): void
    {
        if ($route->status->value !== 'PENDING'
            || ! $route->recipientPosition()->where('code', OrganizationCatalog::MAYOR_POSITION)
                ->whereHas('positionLevel', fn ($level) => $level->where('code', OrganizationCatalog::MAYOR_LEVEL)->where('is_active', true))->exists()) {
            throw ExpertConsultationStateConflict::stale();
        }
    }
}
