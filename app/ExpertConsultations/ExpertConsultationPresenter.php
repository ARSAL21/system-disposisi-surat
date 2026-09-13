<?php

namespace App\ExpertConsultations;

use App\Models\ExpertConsultation;
use App\Models\ExpertConsultationDocument;

final class ExpertConsultationPresenter
{
    /** @return array<string, mixed> */
    public function detail(ExpertConsultation $consultation): array
    {
        $report = $consultation->report;

        return [
            'id' => (int) $consultation->getKey(),
            'letter' => [
                'agenda_number' => $consultation->incomingLetter->agenda_number,
                'subject' => $consultation->incomingLetter->subject,
                'sender_organization_name' => $consultation->incomingLetter->senderOrganization->name,
                'received_at' => $consultation->incomingLetter->received_at->toISOString(),
            ],
            'advisor' => [
                'id' => (int) $consultation->expertPosition->getKey(),
                'name' => $consultation->expertPosition->name,
                'field' => $consultation->expertPosition->name,
                'holder_name' => $consultation->expertPosition->activeAssignment?->user?->name,
                'is_available' => $consultation->status->value === 'PENDING',
            ],
            'status' => $consultation->status->value,
            'requested_at' => $consultation->requested_at->toISOString(),
            'reported_at' => $consultation->reported_at?->toISOString(),
            'request_note' => $consultation->request_note,
            'requested_by' => ['name' => $consultation->requestedBy->name, 'position_name' => $consultation->requestedByPositionAssignment->position->name],
            'report' => $report === null ? null : [
                'summary' => $report->summary,
                'recommendation' => $report->recommendation,
                'reported_at' => $report->created_at->toISOString(),
                'reported_by' => ['name' => $report->reportedBy->name, 'position_name' => $report->reportedByPositionAssignment->position->name],
                'document' => $this->document($consultation->documents->last()),
            ],
            'links' => [
                'show' => route('back-office.expert-consultations.show', $consultation),
                'report' => route('back-office.expert-consultations.report', $consultation),
                'cancel' => route('back-office.executive.inbox.expert-consultations.cancel', [$consultation->letter_route_id, $consultation]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function coordination(ExpertConsultation $consultation): array
    {
        return [
            'id' => (int) $consultation->getKey(),
            'status' => $consultation->status->value,
            'requested_at' => $consultation->requested_at->toISOString(),
            'reported_at' => $consultation->reported_at?->toISOString(),
            'advisor' => ['id' => (int) $consultation->expertPosition->getKey(), 'name' => $consultation->expertPosition->name, 'field' => $consultation->expertPosition->name, 'holder_name' => null, 'is_available' => false],
            'requested_by' => ['name' => $consultation->requestedBy->name, 'position_name' => $consultation->requestedByPositionAssignment->position->name],
        ];
    }

    /** @return array<string, mixed>|null */
    private function document(?ExpertConsultationDocument $document): ?array
    {
        if (! $document instanceof ExpertConsultationDocument) {
            return null;
        }

        return [
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'sha256' => $document->sha256,
            'preview_url' => route('back-office.expert-consultations.documents.preview', [$document->expert_consultation_id, $document]),
            'download_url' => route('back-office.expert-consultations.documents.download', [$document->expert_consultation_id, $document]),
        ];
    }
}
