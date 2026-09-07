<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\OutgoingLetterStatus;
use App\Exceptions\OutgoingLetterStateConflict;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Services\OutgoingLetterLockService;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignOutgoingLetterNumber
{
    public function __construct(
        private readonly OutgoingLetterLockService $lockService,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $recordAudit,
    ) {}

    public function execute(User $actor, OutgoingLetter $target, string $number, string $letterDate): OutgoingLetter
    {
        try {
            return DB::transaction(function () use ($actor, $target, $number, $letterDate): OutgoingLetter {
                $context = $this->lockService->lock($target);
                $outgoing = $context['outgoing'];

                if ($context['dossier']->status !== LetterResponseDossierStatus::Finalized
                    || $outgoing->status !== OutgoingLetterStatus::Authorized) {
                    throw OutgoingLetterStateConflict::stale();
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $assignment = $this->assignmentResolver->lockGeneralAffairsOfficer($lockedActor);
                $date = Date::parse($letterDate)->startOfDay();
                $outgoing->outgoing_number = trim($number);
                $outgoing->agenda_year = (int) $date->year;
                $outgoing->letter_date = $date;
                $outgoing->numbered_by_user_id = $lockedActor->getKey();
                $outgoing->numbered_by_position_assignment_id = $assignment->getKey();
                $outgoing->numbered_at = Date::now();
                $outgoing->status = OutgoingLetterStatus::NumberAssigned;
                $outgoing->save();

                $this->recordAudit->execute(
                    actor: $lockedActor,
                    action: AuditAction::OutgoingLetterNumberAssigned,
                    subjectType: 'incoming_letter',
                    subjectId: $context['letter']->getKey(),
                    oldValues: ['status' => OutgoingLetterStatus::Authorized->value],
                    newValues: [
                        'outgoing_letter_id' => $outgoing->getKey(),
                        'status' => OutgoingLetterStatus::NumberAssigned->value,
                        'agenda_year' => $outgoing->agenda_year,
                    ],
                    actorPositionAssignment: $assignment,
                );

                return $outgoing;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($this->isUniqueNumberConflict($exception)) {
                throw ValidationException::withMessages([
                    'outgoing_number' => 'Nomor surat sudah digunakan pada tahun yang sama.',
                ]);
            }

            throw $exception;
        }
    }

    private function isUniqueNumberConflict(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000'], true)
            && str_contains(strtolower($exception->getMessage()), 'outgoing');
    }
}
