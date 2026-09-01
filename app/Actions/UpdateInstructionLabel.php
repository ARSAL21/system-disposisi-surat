<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\InstructionLabelConflict;
use App\Models\InstructionLabel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateInstructionLabel
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param array{name: string, description: string|null, sort_order: int} $payload */
    public function execute(User $actor, InstructionLabel $instructionLabel, array $payload): InstructionLabel
    {
        return DB::transaction(function () use ($actor, $instructionLabel, $payload): InstructionLabel {
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
            $oldValues = $this->snapshot($label);

            $label->name = $payload['name'];
            $label->description = $payload['description'];
            $label->sort_order = $payload['sort_order'];

            if (! $label->isDirty()) {
                return $label;
            }

            $label->save();

            $this->recordAudit->execute(
                actor: $lockedActor,
                action: AuditAction::InstructionLabelUpdated,
                subjectType: 'instruction_label',
                subjectId: $label->getKey(),
                oldValues: $oldValues,
                newValues: $this->snapshot($label),
            );

            return $label;
        }, attempts: 3);
    }

    /** @return array<string, mixed> */
    private function snapshot(InstructionLabel $label): array
    {
        return [
            'name' => $label->name,
            'description_sha256' => $label->description === null
                ? null
                : hash('sha256', $label->description),
            'sort_order' => $label->sort_order,
        ];
    }
}
