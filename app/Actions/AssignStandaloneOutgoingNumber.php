<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\OutgoingLetterOrigin;
use App\Enums\OutgoingLetterStatus;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetter;
use App\Models\StandaloneOutgoingDocumentVersion;
use App\Models\StandaloneOutgoingDraft;
use App\Models\StandaloneOutgoingReview;
use App\Models\User;
use App\Services\OutgoingLetterPositionAssignmentResolver;
use App\Services\StandaloneOutgoingDocumentStorage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssignStandaloneOutgoingNumber
{
    public function __construct(
        private readonly StandaloneOutgoingDocumentStorage $storage,
        private readonly OutgoingLetterPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, StandaloneOutgoingDraft $target, string $number, string $letterDate): OutgoingLetter
    {
        try {
            return DB::transaction(function () use ($actor, $target, $number, $letterDate): OutgoingLetter {
                $draft = StandaloneOutgoingDraft::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();
                if ($draft->status !== StandaloneOutgoingDraftStatus::AwaitingNumber
                    || OutgoingLetter::query()->where('standalone_outgoing_draft_id', $draft->getKey())->lockForUpdate()->exists()) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                $version = StandaloneOutgoingDocumentVersion::query()
                    ->where('standalone_outgoing_draft_id', $draft->getKey())
                    ->orderByDesc('version_number')
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->storage->validate($draft, $version);

                $approval = StandaloneOutgoingReview::query()
                    ->where('standalone_outgoing_draft_id', $draft->getKey())
                    ->where('stage', 'ASSISTANT')
                    ->where('decision', 'APPROVED')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
                if (! $approval instanceof StandaloneOutgoingReview) {
                    throw StandaloneOutgoingStateConflict::stale();
                }

                $lockedActor = User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();
                $assignment = $this->assignmentResolver->lockGeneralAffairsOfficer($lockedActor);
                $date = Date::parse($letterDate)->startOfDay();

                $outgoing = new OutgoingLetter;
                $outgoing->origin = OutgoingLetterOrigin::Standalone;
                $outgoing->standalone_outgoing_draft_id = $draft->getKey();
                $outgoing->corrects_outgoing_letter_id = $draft->corrects_outgoing_letter_id;
                $outgoing->signatory_position_id = $this->sekdaPositionId();
                $outgoing->subject = $draft->subject;
                $outgoing->status = OutgoingLetterStatus::NumberAssigned;
                $outgoing->outgoing_number = trim($number);
                $outgoing->agenda_year = (int) $date->year;
                $outgoing->letter_date = $date;
                $outgoing->numbered_by_user_id = $lockedActor->getKey();
                $outgoing->numbered_by_position_assignment_id = $assignment->getKey();
                $outgoing->numbered_at = Date::now();
                $outgoing->authorized_by_user_id = $approval->decided_by_user_id;
                $outgoing->authorized_by_position_assignment_id = $approval->decided_by_position_assignment_id;
                $outgoing->authorized_at = $approval->created_at;
                $outgoing->save();

                $draft->status = StandaloneOutgoingDraftStatus::NumberAssigned;
                $draft->save();

                // Penomoran Petugas sekaligus menyerahkan surat bernomor ke meja Sekda;
                // tidak ada keadaan menunggu yang dapat terlewat secara manual.
                $outgoing->status = OutgoingLetterStatus::SekdaReview;
                $outgoing->save();
                $draft->status = StandaloneOutgoingDraftStatus::SekdaReview;
                $draft->save();

                $this->audit->execute(
                    actor: $lockedActor,
                    action: AuditAction::StandaloneOutgoingNumberAssigned,
                    subjectType: 'standalone_outgoing_draft',
                    subjectId: $draft->getKey(),
                    newValues: [
                        'outgoing_letter_id' => $outgoing->getKey(),
                        'agenda_year' => $outgoing->agenda_year,
                        'status' => OutgoingLetterStatus::SekdaReview->value,
                    ],
                    actorPositionAssignment: $assignment,
                );
                $this->audit->execute(
                    actor: $lockedActor,
                    action: AuditAction::StandaloneOutgoingSubmittedToSekda,
                    subjectType: 'standalone_outgoing_draft',
                    subjectId: $draft->getKey(),
                    oldValues: ['status' => OutgoingLetterStatus::NumberAssigned->value],
                    newValues: ['status' => OutgoingLetterStatus::SekdaReview->value, 'outgoing_letter_id' => $outgoing->getKey()],
                    actorPositionAssignment: $assignment,
                );

                return $outgoing;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['19', '23000'], true)) {
                throw ValidationException::withMessages(['outgoing_number' => 'Nomor surat sudah digunakan pada tahun yang sama.']);
            }

            throw $exception;
        }
    }

    private function sekdaPositionId(): int
    {
        $id = DB::table('positions')->where('code', 'SEKDA')->where('is_active', true)->lockForUpdate()->value('id');
        if (! is_numeric($id)) {
            throw StandaloneOutgoingStateConflict::stale();
        }

        return (int) $id;
    }
}
