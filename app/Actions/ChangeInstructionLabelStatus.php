<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\InstructionLabelConflict;
use App\Models\InstructionLabel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeInstructionLabelStatus
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    public function execute(User $actor, InstructionLabel $instructionLabel, bool $isActive): InstructionLabel
    {
        return DB::transaction(function () use ($actor, $instructionLabel, $isActive): InstructionLabel {
            $lockedActor = User::query()
                ->whereKey($actor->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedActor->isInternalAccount()
                || ! $lockedActor->is_active
                || ! $lockedActor->hasVerifiedEmail()
            ) {
                throw InstructionLabelConflict::actorUnavailable();
            }

            $label = InstructionLabel::query()
                ->whereKey($instructionLabel->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($label->is_active === $isActive) {
                return $label;
            }

            if (! $isActive) {
                $activeIds = InstructionLabel::query()
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->pluck('id');

                if ($activeIds->count() <= 1) {
                    throw InstructionLabelConflict::lastActive();
                }
            }

            $oldValues = ['is_active' => $label->is_active];
            $label->is_active = $isActive;
            $label->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::InstructionLabelStatusChanged,
                subjectType: 'instruction_label',
                subjectId: $label->getKey(),
                oldValues: $oldValues,
                newValues: ['is_active' => $label->is_active],
            );

            return $label;
        }, attempts: 3);
    }
}
