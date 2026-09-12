<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Enums\StandaloneOutgoingDraftStatus;
use App\Exceptions\StandaloneOutgoingStateConflict;
use App\Models\OutgoingLetterTemplate;
use App\Models\OutgoingLetterTemplateVersion;
use App\Models\Position;
use App\Models\StandaloneOutgoingCopyRecipient;
use App\Models\StandaloneOutgoingDraft;
use App\Models\User;
use App\Organization\OrganizationCatalog;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Support\Facades\DB;

final class UpdateStandaloneOutgoingDraft
{
    public function __construct(
        private readonly StandaloneOutgoingPositionAssignmentResolver $assignmentResolver,
        private readonly RecordAudit $audit,
    ) {}

    /**
     * @param  array{recipient_name:string,recipient_organization:?string,recipient_position:?string,recipient_address:?string,recipient_email:?string,subject:string,summary:?string}  $attributes
     * @param  list<int>  $copyPositionIds
     */
    public function execute(User $actor, StandaloneOutgoingDraft $draft, int $templateVersionId, array $attributes, array $copyPositionIds): StandaloneOutgoingDraft
    {
        return DB::transaction(function () use ($actor, $draft, $templateVersionId, $attributes, $copyPositionIds): StandaloneOutgoingDraft {
            $lockedDraft = StandaloneOutgoingDraft::query()->whereKey($draft->getKey())->lockForUpdate()->firstOrFail();
            if (! in_array($lockedDraft->status, [StandaloneOutgoingDraftStatus::Draft, StandaloneOutgoingDraftStatus::RevisionRequired], true)
                || (int) $lockedDraft->created_by_user_id !== (int) $actor->getKey()) {
                throw StandaloneOutgoingStateConflict::stale();
            }

            $templateId = OutgoingLetterTemplateVersion::query()->whereKey($templateVersionId)->value('outgoing_letter_template_id');
            if ($templateId === null) {
                throw StandaloneOutgoingStateConflict::invalidTemplate();
            }
            $template = OutgoingLetterTemplate::query()->whereKey($templateId)->lockForUpdate()->firstOrFail();
            $templateVersion = OutgoingLetterTemplateVersion::query()
                ->whereKey($templateVersionId)
                ->where('outgoing_letter_template_id', $template->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if (! $template->is_active
                || (int) $template->organizational_unit_id !== (int) $lockedDraft->organizational_unit_id) {
                throw StandaloneOutgoingStateConflict::invalidTemplate();
            }

            $assignment = $this->assignmentResolver->lockDeliveryActorAssignmentForUnit($actor, $lockedDraft->organizational_unit_id);

            $copyPositionIds = $this->lockValidCopyPositions($copyPositionIds);
            $old = ['subject' => $lockedDraft->subject, 'template_version_id' => $lockedDraft->outgoing_letter_template_version_id];
            $lockedDraft->outgoing_letter_template_version_id = $templateVersion->getKey();
            $lockedDraft->recipient_name = $attributes['recipient_name'];
            $lockedDraft->recipient_organization = $attributes['recipient_organization'];
            $lockedDraft->recipient_position = $attributes['recipient_position'];
            $lockedDraft->recipient_address = $attributes['recipient_address'];
            $lockedDraft->recipient_email = $attributes['recipient_email'];
            $lockedDraft->subject = $attributes['subject'];
            $lockedDraft->summary = $attributes['summary'];
            $lockedDraft->save();

            StandaloneOutgoingCopyRecipient::query()->where('standalone_outgoing_draft_id', $lockedDraft->getKey())->delete();
            foreach ($copyPositionIds as $positionId) {
                $copyRecipient = new StandaloneOutgoingCopyRecipient;
                $copyRecipient->standalone_outgoing_draft_id = $lockedDraft->getKey();
                $copyRecipient->position_id = $positionId;
                $copyRecipient->save();
            }

            $this->audit->execute(
                $actor,
                AuditAction::StandaloneOutgoingDraftUpdated,
                'standalone_outgoing_draft',
                $lockedDraft->getKey(),
                oldValues: $old,
                newValues: ['subject' => $lockedDraft->subject, 'template_version_id' => $lockedDraft->outgoing_letter_template_version_id],
                actorPositionAssignment: $assignment,
            );

            return $lockedDraft;
        }, attempts: 3);
    }

    /**
     * @param  list<int>  $copyPositionIds
     * @return list<int>
     */
    private function lockValidCopyPositions(array $copyPositionIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $copyPositionIds)));
        if ($ids === []) {
            return [];
        }

        $positions = Position::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereHas('positionLevel', fn ($level) => $level->where('is_active', true)->whereIn('code', [
                OrganizationCatalog::EXECUTIVE_ENTRY_LEVEL,
                OrganizationCatalog::ASSISTANT_LEVEL,
                OrganizationCatalog::SECTION_HEAD_LEVEL,
            ]))
            ->lockForUpdate()
            ->get();

        if ($positions->count() !== count($ids)) {
            throw StandaloneOutgoingStateConflict::invalidCopyRecipient();
        }

        return array_values($positions->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all());
    }
}
