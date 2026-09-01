<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Exceptions\InstructionLabelConflict;
use App\Models\InstructionLabel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInstructionLabel
{
    public function __construct(private readonly RecordAudit $recordAudit) {}

    /** @param array{code: string, name: string, description: string|null, sort_order: int} $payload */
    public function execute(User $actor, array $payload): InstructionLabel
    {
        try {
            return DB::transaction(function () use ($actor, $payload): InstructionLabel {
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

                $label = new InstructionLabel;
                $label->code = $payload['code'];
                $label->name = $payload['name'];
                $label->description = $payload['description'];
                $label->sort_order = $payload['sort_order'];
                $label->is_active = true;
                $label->save();

                $this->recordAudit->execute(
                    actor: $lockedActor,
                    action: AuditAction::InstructionLabelCreated,
                    subjectType: 'instruction_label',
                    subjectId: $label->getKey(),
                    newValues: $this->snapshot($label),
                );

                return $label;
            }, attempts: 3);
        } catch (QueryException $exception) {
            if ($this->isDuplicateCodeViolation($exception)) {
                throw ValidationException::withMessages([
                    'code' => 'Kode instruksi sudah digunakan.',
                ]);
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(InstructionLabel $label): array
    {
        return [
            'code' => $label->code,
            'name' => $label->name,
            'description_sha256' => $label->description === null
                ? null
                : hash('sha256', $label->description),
            'sort_order' => $label->sort_order,
            'is_active' => $label->is_active,
        ];
    }

    private function isDuplicateCodeViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'instruction_labels_code_unique')
            || str_contains($message, 'instruction_labels.code');
    }
}
