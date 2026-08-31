<?php

namespace App\Documents;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use Illuminate\Database\Eloquent\Collection;

final class DocumentVersionHistoryQuery
{
    /**
     * @return array{
     *     versions: Collection<int, LetterDocument>,
     *     audits: Collection<int, AuditLog>
     * }
     */
    public function execute(IncomingLetter $incomingLetter): array
    {
        $versions = $incomingLetter->documents()
            ->with([
                'uploadedBy:id,name,account_type',
                'sourceSubmissionDocument:id,letter_submission_id',
                'sourceSubmissionDocument.submission:id,source',
                'replacesDocument:id,incoming_letter_id,version_number',
            ])
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->get();

        $audits = AuditLog::query()
            ->where('action', AuditAction::DocumentVersionCreated->value)
            ->where('subject_type', 'letter_document')
            ->whereIn('subject_id', $versions->modelKeys())
            ->with([
                'actor:id,name,account_type',
                'actorPositionAssignment:id,user_id,position_id',
                'actorPositionAssignment.position:id,organizational_unit_id,name',
                'actorPositionAssignment.position.organizationalUnit:id,name',
            ])
            ->orderBy('id')
            ->get()
            ->keyBy(fn (AuditLog $audit): int => (int) $audit->subject_id);

        return compact('versions', 'audits');
    }
}
