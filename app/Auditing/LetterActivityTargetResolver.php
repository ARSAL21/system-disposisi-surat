<?php

namespace App\Auditing;

use App\Models\AuditLog;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\LetterSubmission;
use Illuminate\Support\Collection;

final class LetterActivityTargetResolver
{
    /**
     * @param  Collection<int, AuditLog>  $audits
     * @return array<int, array{target: array<string, mixed>, document: array<string, mixed>|null}>
     */
    public function resolve(Collection $audits): array
    {
        $submissions = LetterSubmission::query()
            ->with('incomingLetter:id,letter_submission_id,agenda_number')
            ->whereKey($this->subjectIds($audits, 'letter_submission'))
            ->get(['id', 'public_id', 'source', 'subject', 'sender_organization_name'])
            ->keyBy('id');
        $letters = IncomingLetter::query()
            ->with([
                'submission:id,public_id,source',
                'senderOrganization:id,name',
            ])
            ->whereKey($this->subjectIds($audits, 'incoming_letter'))
            ->get(['id', 'letter_submission_id', 'agenda_number', 'sender_organization_id', 'subject'])
            ->keyBy('id');
        $documents = LetterDocument::query()
            ->with([
                'incomingLetter:id,letter_submission_id,agenda_number,sender_organization_id,subject',
                'incomingLetter.submission:id,public_id,source',
                'incomingLetter.senderOrganization:id,name',
            ])
            ->whereKey($this->subjectIds($audits, 'letter_document'))
            ->get(['id', 'incoming_letter_id', 'version_number', 'sha256'])
            ->keyBy('id');
        $routes = LetterRoute::query()
            ->with([
                'incomingLetter:id,letter_submission_id,agenda_number,sender_organization_id,subject',
                'incomingLetter.submission:id,public_id,source',
                'incomingLetter.senderOrganization:id,name',
            ])
            ->whereKey($this->subjectIds($audits, 'letter_route'))
            ->get(['id', 'incoming_letter_id'])
            ->keyBy('id');
        $resolved = [];

        foreach ($audits as $audit) {
            $resolved[$audit->getKey()] = match ($audit->subject_type) {
                'letter_submission' => $this->fromSubmission(
                    $submissions->get($audit->subject_id),
                    $audit,
                ),
                'incoming_letter' => $this->fromIncomingLetter(
                    $letters->get($audit->subject_id),
                    $audit,
                ),
                'letter_document' => $this->fromDocument(
                    $documents->get($audit->subject_id),
                    $audit,
                ),
                'letter_route' => $this->fromRoute(
                    $routes->get($audit->subject_id),
                    $audit,
                ),
                default => $this->fallback($audit),
            };
        }

        return $resolved;
    }

    /** @return array{target: array<string, mixed>, document: null} */
    private function fromSubmission(?LetterSubmission $submission, AuditLog $audit): array
    {
        if (! $submission instanceof LetterSubmission) {
            return $this->fallback($audit);
        }

        return [
            'target' => [
                'public_id' => $submission->public_id,
                'agenda_number' => $submission->incomingLetter?->agenda_number,
                'subject' => $submission->subject,
                'sender' => $submission->sender_organization_name,
                'source' => $submission->source->value,
            ],
            'document' => null,
        ];
    }

    /** @return array{target: array<string, mixed>, document: null} */
    private function fromIncomingLetter(?IncomingLetter $letter, AuditLog $audit): array
    {
        if (! $letter instanceof IncomingLetter) {
            return $this->fallback($audit);
        }

        return [
            'target' => [
                'public_id' => $letter->submission->public_id,
                'agenda_number' => $letter->agenda_number,
                'subject' => $letter->subject,
                'sender' => $letter->senderOrganization->name,
                'source' => $letter->submission->source->value,
            ],
            'document' => null,
        ];
    }

    /** @return array{target: array<string, mixed>, document: array<string, mixed>|null} */
    private function fromDocument(?LetterDocument $document, AuditLog $audit): array
    {
        if (! $document instanceof LetterDocument) {
            return $this->fallback($audit);
        }

        $letter = $document->incomingLetter;

        return [
            'target' => [
                'public_id' => $letter->submission->public_id,
                'agenda_number' => $letter->agenda_number,
                'subject' => $letter->subject,
                'sender' => $letter->senderOrganization->name,
                'source' => $letter->submission->source->value,
            ],
            'document' => [
                'version_number' => $document->version_number,
                'sha256' => $document->sha256,
            ],
        ];
    }

    /** @return array{target: array<string, mixed>, document: null} */
    private function fromRoute(?LetterRoute $route, AuditLog $audit): array
    {
        if (! $route instanceof LetterRoute) {
            return $this->fallback($audit);
        }

        return $this->fromIncomingLetter($route->incomingLetter, $audit);
    }

    /** @return array{target: array<string, mixed>, document: null} */
    private function fallback(AuditLog $audit): array
    {
        return [
            'target' => [
                'public_id' => $audit->metadata['public_id']
                    ?? $audit->metadata['submission_public_id']
                    ?? null,
                'agenda_number' => $audit->new_values['agenda_number'] ?? null,
                'subject' => 'Identitas surat tidak tersedia',
                'sender' => 'Snapshot audit',
                'source' => null,
            ],
            'document' => null,
        ];
    }

    /**
     * @param  Collection<int, AuditLog>  $audits
     * @return list<int>
     */
    private function subjectIds(Collection $audits, string $subjectType): array
    {
        return array_values($audits
            ->where('subject_type', $subjectType)
            ->pluck('subject_id')
            ->filter(fn (mixed $id): bool => is_int($id))
            ->unique()
            ->values()
            ->all());
    }
}
