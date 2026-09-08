<?php

namespace App\LetterResponses;

use App\Enums\DispositionRecipientStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\LetterResponseDocumentKind;
use App\Enums\LetterResponseDossierStatus;
use App\Enums\PermissionName;
use App\Models\Disposition;
use App\Models\DispositionRecipient;
use App\Models\LetterResponseDocument;
use App\Models\LetterResponseDocumentVersion;
use App\Models\LetterResponseDossier;
use App\Models\LetterResponseReview;
use App\Models\OrganizationalUnit;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Reporting\ReportScopeResolver;
use Illuminate\Support\Collection;

final class LetterResponsePresenter
{
    public function __construct(private readonly ReportScopeResolver $scopeResolver) {}

    /** @return array<string, mixed> */
    public function dossier(LetterResponseDossier $dossier, User $user): array
    {
        $this->load($dossier);
        $scope = $this->scopeResolver->resolve($user);
        abort_if($scope === null, 404);

        $letter = $dossier->incomingLetter;
        $route = $letter->routes->sortBy('id')->first();
        abort_if($route === null, 409, 'Routing awal surat tidak konsisten.');
        $isExecutive = in_array($route->recipient_position_id, $scope->executivePositionIds, true);
        $initialDisposition = $letter->dispositions->first(fn (Disposition $item): bool => $item->source_route_id !== null);
        abort_if(! $initialDisposition instanceof Disposition, 409, 'Graph disposisi surat tidak konsisten.');
        $documents = $dossier->documents;
        $reviews = $dossier->reviews;
        $assistants = [];

        foreach ($initialDisposition->recipients->sortBy([['received_at', 'asc'], ['id', 'asc']]) as $assistantRecipient) {
            $ownsAssistant = in_array($assistantRecipient->recipient_position_id, $scope->assistantPositionIds, true);
            $childDisposition = $letter->dispositions->first(
                fn (Disposition $item): bool => $item->parent_recipient_id === $assistantRecipient->getKey(),
            );
            $children = $childDisposition instanceof Disposition
                ? $childDisposition->recipients->sortBy([['received_at', 'asc'], ['id', 'asc']])
                : collect();
            $visibleChildren = $isExecutive || $ownsAssistant
                ? $children
                : $children->filter(fn (DispositionRecipient $child): bool => in_array(
                    $child->recipient_position_id,
                    $scope->sectionHeadPositionIds,
                    true,
                ));

            if (! $isExecutive && ! $ownsAssistant && $visibleChildren->isEmpty()) {
                continue;
            }

            $proposal = $documents->first(fn (LetterResponseDocument $document): bool => $document->kind === LetterResponseDocumentKind::AssistantProposal
                && $document->source_recipient_id === $assistantRecipient->getKey());
            $canSubmitProposal = $user->can(PermissionName::ContributeLetterResponses->value)
                && $dossier->status === LetterResponseDossierStatus::Open
                && $proposal === null
                && $children->isNotEmpty()
                && $children->every(fn (DispositionRecipient $child): bool => $child->status === DispositionRecipientStatus::Completed)
                && $this->ownsPosition($user, $assistantRecipient->recipient_position_id);
            $assistants[] = [
                'public_id' => 'assistant-'.$assistantRecipient->recipientPosition->code,
                'position_name' => $assistantRecipient->recipientPosition->name,
                'unit_name' => $assistantRecipient->recipientPosition->organizationalUnit instanceof OrganizationalUnit
                    ? $assistantRecipient->recipientPosition->organizationalUnit->name
                    : 'Sekretariat Daerah',
                'official_name' => $this->officialName($assistantRecipient->recipientPosition),
                'status' => $assistantRecipient->status->value,
                'forwarded_at' => $childDisposition?->created_at?->toISOString(),
                'child_progress' => $this->progress($visibleChildren),
                'children' => $visibleChildren->map(function (DispositionRecipient $child) use ($documents, $reviews, $dossier, $user): array {
                    $material = $documents->first(fn (LetterResponseDocument $document): bool => $document->kind === LetterResponseDocumentKind::TechnicalMaterial
                        && $document->source_recipient_id === $child->getKey());
                    $canUploadMaterial = $user->can(PermissionName::ContributeLetterResponses->value)
                        && $dossier->status === LetterResponseDossierStatus::Open
                        && $child->status === DispositionRecipientStatus::Completed
                        && $material === null
                        && $this->ownsPosition($user, $child->recipient_position_id);

                    return [
                        'public_id' => 'section-'.$child->recipientPosition->code,
                        'position_name' => $child->recipientPosition->name,
                        'unit_name' => $child->recipientPosition->organizationalUnit instanceof OrganizationalUnit
                            ? $child->recipientPosition->organizationalUnit->name
                            : 'Sekretariat Daerah',
                        'official_name' => $this->officialName($child->recipientPosition),
                        'status' => $child->status->value,
                        'received_at' => $child->received_at?->toISOString(),
                        'started_at' => $child->started_at?->toISOString(),
                        'completed_at' => $child->completed_at?->toISOString(),
                        'completion_note' => $child->completion_note,
                        'material' => $material instanceof LetterResponseDocument
                            ? $this->document($material, $reviews, $dossier, $user)
                            : null,
                        'can_upload_material' => $canUploadMaterial,
                        'routes' => $canUploadMaterial ? [
                            'material_store' => route('back-office.letter-responses.materials.store', [$dossier, $child]),
                        ] : [],
                    ];
                })->values()->all(),
                'proposal' => ($isExecutive || $ownsAssistant) && $proposal instanceof LetterResponseDocument
                    ? $this->document($proposal, $reviews, $dossier, $user)
                    : null,
                'can_submit_proposal' => $canSubmitProposal,
                'can_return_material' => $ownsAssistant && $user->can(PermissionName::ReviewLetterResponses->value),
                'routes' => $canSubmitProposal ? [
                    'proposal_store' => route('back-office.letter-responses.proposals.store', [$dossier, $assistantRecipient]),
                ] : [],
            ];
        }

        $visibleTerminalRecipients = collect($assistants)->flatMap(fn (array $assistant): array => $assistant['children']);
        $progress = [
            'total' => $visibleTerminalRecipients->count(),
            'completed' => $visibleTerminalRecipients->where('status', DispositionRecipientStatus::Completed->value)->count(),
        ];
        $progress['percent'] = $progress['total'] > 0
            ? (int) round(($progress['completed'] / $progress['total']) * 100)
            : 0;

        return [
            'public_id' => $dossier->public_id,
            'status' => $dossier->status->value,
            'opened_at' => $dossier->opened_at->toISOString(),
            'finalized_at' => $dossier->finalized_at?->toISOString(),
            'letter' => [
                'reference' => $letter->submission->public_id,
                'agenda_number' => $letter->agenda_number,
                'subject' => $letter->subject,
                'sender_organization_name' => $letter->senderOrganization->name,
                'source' => $letter->submission->source->value,
                'status' => $letter->status->value,
                'received_at' => $letter->received_at->toISOString(),
            ],
            'executive' => [
                'position_name' => $route->recipientPosition->name,
                'official_name' => $this->officialName($route->recipientPosition),
                'role' => 'EXECUTIVE',
            ],
            'assistants' => $assistants,
            'consolidation' => $isExecutive
                ? (($consolidation = $documents->first(fn (LetterResponseDocument $document): bool => $document->kind === LetterResponseDocumentKind::ExecutiveConsolidation)) instanceof LetterResponseDocument
                    ? $this->document($consolidation, $reviews, $dossier, $user)
                    : null)
                : null,
            'progress' => $progress,
            'mandates' => $isExecutive
                ? $dossier->mandates->map(fn (OutgoingLetter $mandate): array => $this->mandate($mandate))->values()->all()
                : [],
            'eligible_signatories' => $isExecutive ? $this->eligibleSignatories($route->recipientPosition, $initialDisposition) : [],
            'viewer' => [
                'role' => $isExecutive ? 'EXECUTIVE' : ($scope->assistantPositionIds !== [] ? 'ASSISTANT' : 'SECTION_HEAD'),
                'display_name' => $user->name,
                'can_view' => true,
                'can_contribute' => $user->can(PermissionName::ContributeLetterResponses->value),
                'can_review' => $user->can(PermissionName::ReviewLetterResponses->value),
                'can_authorize' => $isExecutive && $user->can(PermissionName::AuthorizeLetterResponses->value),
            ],
            'links' => [
                'index' => route('back-office.letter-responses.index'),
                'consolidation_store' => $isExecutive
                    && $dossier->status === LetterResponseDossierStatus::Open
                    && $letter->status === IncomingLetterStatus::Completed
                    && $user->can(PermissionName::ContributeLetterResponses->value)
                    ? route('back-office.letter-responses.consolidations.store', $dossier)
                    : null,
                'mandate_store' => $isExecutive
                    && $dossier->status === LetterResponseDossierStatus::Open
                    && $letter->status === IncomingLetterStatus::Completed
                    && $user->can(PermissionName::AuthorizeLetterResponses->value)
                    ? route('back-office.letter-responses.mandates.store', $dossier)
                    : null,
                'finalize' => $isExecutive
                    && $dossier->status === LetterResponseDossierStatus::Open
                    && $letter->status === IncomingLetterStatus::Completed
                    && $user->can(PermissionName::AuthorizeLetterResponses->value)
                    ? route('back-office.letter-responses.finalize', $dossier)
                    : null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function listItem(LetterResponseDossier $dossier, User $user): array
    {
        $presented = $this->dossier($dossier, $user);
        $assistants = $presented['assistants'] ?? null;
        abort_unless(is_array($assistants), 409, 'Payload dossier balasan tidak konsisten.');

        return [
            'public_id' => $presented['public_id'],
            'status' => $presented['status'],
            'letter' => $presented['letter'],
            'progress' => $presented['progress'],
            'mandates' => $presented['mandates'],
            'assistants_count' => count($assistants),
            'updated_at' => $dossier->updated_at?->toISOString() ?? $dossier->opened_at->toISOString(),
            'links' => ['detail' => route('back-office.letter-responses.show', $dossier)],
        ];
    }

    private function load(LetterResponseDossier $dossier): void
    {
        $dossier->loadMissing([
            'incomingLetter.submission:id,public_id,source',
            'incomingLetter.senderOrganization:id,name',
            'incomingLetter.routes.recipientPosition.positionLevel:id,code',
            'incomingLetter.routes.recipientPosition.organizationalUnit:id,name',
            'incomingLetter.routes.recipientPosition.activeAssignment.user:id,name',
            'incomingLetter.dispositions.recipients.recipientPosition.positionLevel:id,code',
            'incomingLetter.dispositions.recipients.recipientPosition.organizationalUnit:id,name',
            'incomingLetter.dispositions.recipients.recipientPosition.activeAssignment.user:id,name',
            'documents.ownerPosition:id,code,name',
            'documents.sourceRecipient.disposition.parentRecipient:id,recipient_position_id',
            'documents.versions.uploadedBy:id,name',
            'reviews',
            'mandates.signatoryPosition.activeAssignment.user:id,name',
            'mandates.sourceDocumentVersion:id,version_number',
        ]);
    }

    /**
     * @param  Collection<int, LetterResponseReview>  $reviews
     * @return array<string, mixed>
     */
    private function document(
        LetterResponseDocument $document,
        Collection $reviews,
        LetterResponseDossier $dossier,
        User $user,
    ): array {
        $versions = $document->versions->map(fn (LetterResponseDocumentVersion $version): array => [
            'public_id' => $version->public_id,
            'version_number' => $version->version_number,
            'original_filename' => $version->original_filename,
            'mime_type' => $version->mime_type,
            'size_bytes' => $version->size_bytes,
            'sha256_fingerprint' => $version->sha256,
            'uploaded_by' => $version->uploadedBy->name,
            'uploaded_at' => $version->created_at->toISOString(),
            'revision_note' => $version->revision_note,
            'links' => [
                'preview' => route('back-office.letter-responses.documents.preview', [$dossier, $version]),
                'download' => route('back-office.letter-responses.documents.download', [$dossier, $version]),
            ],
        ])->values();
        $current = $document->versions->sortByDesc('version_number')->first();
        $review = $current instanceof LetterResponseDocumentVersion
            ? $reviews->firstWhere('document_version_id', $current->getKey())
            : null;
        $ownsDocument = $this->ownsPosition($user, $document->owner_position_id);
        $canUpload = $dossier->status === LetterResponseDossierStatus::Open
            && $ownsDocument
            && $user->can(PermissionName::ContributeLetterResponses->value)
            && ($document->kind === LetterResponseDocumentKind::ExecutiveConsolidation
                || $review instanceof LetterResponseReview);
        $canReturn = $review === null
            && $user->can(PermissionName::ReviewLetterResponses->value)
            && $this->canReturnDocument($user, $dossier, $document);

        return [
            'public_id' => $document->public_id,
            'kind' => $document->kind->value,
            'title' => match ($document->kind) {
                LetterResponseDocumentKind::TechnicalMaterial => 'Bahan teknis '.$document->ownerPosition->name,
                LetterResponseDocumentKind::AssistantProposal => 'Usulan balasan '.$document->ownerPosition->name,
                LetterResponseDocumentKind::ExecutiveConsolidation => 'Konsolidasi '.$document->ownerPosition->name,
            },
            'status' => $review instanceof LetterResponseReview ? 'REVISION_REQUIRED' : 'READY',
            'current_version' => $versions->last(),
            'versions' => $versions->all(),
            'can_upload' => $canUpload,
            'can_return' => $canReturn,
            'can_select' => $document->kind === LetterResponseDocumentKind::AssistantProposal
                && ! $review instanceof LetterResponseReview
                && $dossier->status === LetterResponseDossierStatus::Open
                && $dossier->incomingLetter->status === IncomingLetterStatus::Completed
                && $user->can(PermissionName::AuthorizeLetterResponses->value),
            'open_revision_reason' => $review instanceof LetterResponseReview ? $review->reason : null,
            'routes' => array_filter([
                'revision_store' => $canUpload
                    ? route('back-office.letter-responses.documents.versions.store', [$dossier, $document])
                    : null,
                'return' => $canReturn
                    ? route('back-office.letter-responses.documents.return', [$dossier, $document])
                    : null,
            ]),
        ];
    }

    /**
     * @param  Collection<int, DispositionRecipient>  $recipients
     * @return array{total: int, completed: int, percent: int}
     */
    private function progress(Collection $recipients): array
    {
        $total = $recipients->count();
        $completed = $recipients->filter(
            fn (DispositionRecipient $recipient): bool => $recipient->status === DispositionRecipientStatus::Completed,
        )->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    /** @return array<string, mixed> */
    private function mandate(OutgoingLetter $mandate): array
    {
        return [
            'public_id' => $mandate->public_id,
            'subject' => $mandate->subject,
            'version_number' => $mandate->sourceDocumentVersion->version_number,
            'signatory_name' => $this->officialName($mandate->signatoryPosition) ?? 'Pejabat aktif',
            'signatory_position' => $mandate->signatoryPosition->name,
            'status' => $mandate->status->value,
            'created_at' => $mandate->authorized_at->toISOString(),
        ];
    }

    /** @return list<array{code: string, name: string, official_name: string|null}> */
    private function eligibleSignatories(Position $executive, Disposition $initialDisposition): array
    {
        return array_values(collect([$executive, ...$initialDisposition->recipients->map(
            fn (DispositionRecipient $recipient): Position => $recipient->recipientPosition,
        )->all()])->unique('id')->map(fn (Position $position): array => [
            'code' => $position->code,
            'name' => $position->name,
            'official_name' => $this->officialName($position),
        ])->values()->all());
    }

    private function officialName(Position $position): ?string
    {
        $assignment = $position->activeAssignment;

        return $assignment instanceof PositionAssignment ? $assignment->user->name : null;
    }

    private function ownsPosition(User $user, int $positionId): bool
    {
        return $user->positionAssignments()
            ->where('position_id', $positionId)
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->exists();
    }

    private function canReturnDocument(
        User $user,
        LetterResponseDossier $dossier,
        LetterResponseDocument $document,
    ): bool {
        if ($document->kind === LetterResponseDocumentKind::AssistantProposal) {
            $executivePositionId = (int) $dossier->incomingLetter->routes->sortBy('id')->first()?->recipient_position_id;

            return $executivePositionId > 0 && $this->ownsPosition($user, $executivePositionId);
        }

        if ($document->kind !== LetterResponseDocumentKind::TechnicalMaterial) {
            return false;
        }

        $parent = $document->sourceRecipient?->disposition->parentRecipient;

        return $parent instanceof DispositionRecipient
            && $this->ownsPosition($user, $parent->recipient_position_id);
    }
}
