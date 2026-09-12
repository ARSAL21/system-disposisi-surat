<?php

namespace App\Routing;

use App\Enums\AccountType;
use App\Exceptions\DocumentStorageConflict;
use App\Models\DispositionRecipient;
use App\Models\IncomingLetter;
use App\Models\LetterDocument;
use App\Models\LetterRoute;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Services\DocumentStorageGuard;

final class LetterRoutingPresenter
{
    public function __construct(
        private readonly DocumentStorageGuard $storageGuard,
    ) {}

    /** @return array<string, mixed> */
    public function routingLetter(IncomingLetter $letter): array
    {
        return $this->letter($letter, null, null);
    }

    /** @return array<string, mixed> */
    public function inboxRoute(LetterRoute $letterRoute): array
    {
        return [
            'route_id' => $letterRoute->getKey(),
            'letter' => $this->letter($letterRoute->incomingLetter, $letterRoute, null),
            'received_in_inbox_at' => $letterRoute->routed_at->toISOString(),
            'links' => [
                'show' => route('back-office.executive.inbox.show', $letterRoute),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function dispositionInboxLetter(
        IncomingLetter $letter,
        DispositionRecipient $recipient,
    ): array {
        return $this->letter($letter, null, $recipient);
    }

    /** @return array<string, mixed> */
    public function executiveInboxRecipientLetter(IncomingLetter $letter, DispositionRecipient $recipient): array
    {
        $presented = $this->letter($letter, null, $recipient);
        $presented['links']['show'] = route('back-office.executive.inbox.recipient.show', $recipient);
        $presented['current_document']['preview_url'] = route('back-office.executive.inbox.recipient.document.preview', $recipient);
        $presented['current_document']['download_url'] = route('back-office.executive.inbox.recipient.document.download', $recipient);

        return $presented;
    }

    /**
     * @param  iterable<array{path: string, label: string, description: string, position: Position|null}>  $options
     * @return list<array<string, mixed>>
     */
    public function initialRouteOptions(iterable $options): array
    {
        $presented = [];

        foreach ($options as $option) {
            $presented[] = [
                'path' => $option['path'],
                'label' => $option['label'],
                'description' => $option['description'],
                'target_position' => $option['position'] instanceof Position
                    ? $this->position($option['position'])
                    : null,
                'is_available' => $option['position'] instanceof Position
                    && $this->isPositionAvailable($option['position']),
            ];
        }

        return $presented;
    }

    /** @return array<string, mixed> */
    private function letter(
        IncomingLetter $letter,
        ?LetterRoute $inboxRoute,
        ?DispositionRecipient $dispositionRecipient,
    ): array {
        $document = $letter->currentDocument;
        if (! $document instanceof LetterDocument) {
            throw DocumentStorageConflict::invalidMetadata();
        }

        $this->storageGuard->validateOfficialLetterDocument($letter, $document);
        $route = $inboxRoute ?? $letter->currentRoute;

        return [
            'id' => $letter->getKey(),
            'agenda_number' => $letter->agenda_number,
            'agenda_year' => $letter->agenda_year,
            'subject' => $letter->subject,
            'sender_organization_name' => $letter->senderOrganization->name,
            'external_letter_number' => $letter->external_letter_number,
            'received_at' => $letter->received_at->toISOString(),
            'status' => $letter->status->value,
            'current_document' => $this->document($letter, $document, $inboxRoute, $dispositionRecipient),
            'current_route' => $route instanceof LetterRoute
                ? $this->routeReceipt($route)
                : null,
            'links' => [
                'show' => $dispositionRecipient instanceof DispositionRecipient
                    ? route('back-office.dispositions.inbox.show', $dispositionRecipient)
                    : ($inboxRoute instanceof LetterRoute
                        ? route('back-office.executive.inbox.show', $inboxRoute)
                        : route('back-office.letter-routing.show', $letter)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function document(
        IncomingLetter $letter,
        LetterDocument $document,
        ?LetterRoute $inboxRoute,
        ?DispositionRecipient $dispositionRecipient,
    ): array {
        return [
            'version_number' => $document->version_number,
            'original_filename' => $document->original_filename,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'sha256' => strtolower($document->sha256),
            'recorded_at' => $document->created_at->toISOString(),
            'preview_url' => $dispositionRecipient instanceof DispositionRecipient
                ? route('back-office.dispositions.inbox.document.preview', $dispositionRecipient)
                : ($inboxRoute instanceof LetterRoute
                    ? route('back-office.executive.inbox.document.preview', $inboxRoute)
                    : route('back-office.letter-routing.document.preview', $letter)),
            'download_url' => $dispositionRecipient instanceof DispositionRecipient
                ? route('back-office.dispositions.inbox.document.download', $dispositionRecipient)
                : ($inboxRoute instanceof LetterRoute
                    ? route('back-office.executive.inbox.document.download', $inboxRoute)
                    : route('back-office.letter-routing.document.download', $letter)),
        ];
    }

    /** @return array<string, mixed> */
    private function routeReceipt(LetterRoute $letterRoute): array
    {
        $assignment = $letterRoute->routedByPositionAssignment;
        $position = $assignment?->position;

        return [
            'status' => $letterRoute->status->value,
            'target_position' => $this->position($letterRoute->recipientPosition),
            'routed_by' => [
                'name' => $letterRoute->routedBy->name,
                'position' => $position === null
                    ? 'Posisi historis tidak tersedia'
                    : $position->name,
                'unit' => $position === null
                    ? null
                    : $position->organizationalUnit?->name,
            ],
            'routed_at' => $letterRoute->routed_at->toISOString(),
        ];
    }

    /** @return array{id: int, code: string, name: string, holder_name: string|null, is_available: bool} */
    private function position(Position $position): array
    {
        $assignment = $position->activeAssignment;
        $holder = $assignment instanceof PositionAssignment
            ? $assignment->user
            : null;
        $isAvailable = $this->isPositionAvailable($position);

        return [
            'id' => (int) $position->getKey(),
            'code' => $position->code,
            'name' => $position->name,
            'holder_name' => $isAvailable ? $holder->name : null,
            'is_available' => $isAvailable,
        ];
    }

    private function isPositionAvailable(Position $position): bool
    {
        $assignment = $position->activeAssignment;
        $holder = $assignment instanceof PositionAssignment ? $assignment->user : null;

        return $position->is_active
            && $assignment instanceof PositionAssignment
            && $assignment->started_at->lessThanOrEqualTo(now())
            && $holder !== null
            && $holder->account_type === AccountType::InternalAccount
            && $holder->is_active
            && $holder->hasVerifiedEmail();
    }
}
