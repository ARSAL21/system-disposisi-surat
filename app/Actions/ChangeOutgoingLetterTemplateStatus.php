<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\OutgoingLetterTemplate;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class ChangeOutgoingLetterTemplateStatus
{
    public function __construct(
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(User $actor, OutgoingLetterTemplate $template, bool $isActive): OutgoingLetterTemplate
    {
        return DB::transaction(function () use ($actor, $template, $isActive): OutgoingLetterTemplate {
            $lockedTemplate = OutgoingLetterTemplate::query()->whereKey($template->getKey())->lockForUpdate()->firstOrFail();
            $assignment = $this->assignmentResolver->lockSectionHeadAssignmentForUnit($actor, $lockedTemplate->organizational_unit_id);
            $old = $lockedTemplate->is_active;
            $lockedTemplate->is_active = $isActive;
            $lockedTemplate->save();

            $this->audit->execute(
                $actor,
                AuditAction::OutgoingTemplateStatusChanged,
                'outgoing_letter_template',
                $lockedTemplate->getKey(),
                oldValues: ['is_active' => $old],
                newValues: ['is_active' => $isActive],
                actorPositionAssignment: $assignment,
            );

            return $lockedTemplate;
        }, attempts: 3);
    }
}
