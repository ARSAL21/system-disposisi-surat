<?php

namespace App\Auditing\Contracts;

use App\Auditing\Enums\AuditDomain;
use App\Auditing\Enums\AuditMutationType;
use App\Auditing\Enums\PositionAssignmentRequirement;
use App\Auditing\Exceptions\AuditContractViolationException;
use App\Enums\AuditAction;

final class AuditActionContractRegistry
{
    /**
     * @var array<string, AuditContract>|null
     */
    private static ?array $contracts = null;

    public static function get(AuditAction $action): AuditContract
    {
        $contracts = self::all();

        if (! isset($contracts[$action->value])) {
            throw AuditContractViolationException::forAction(
                $action->value,
                'No audit contract is defined for this action in the registry.',
            );
        }

        return $contracts[$action->value];
    }

    /**
     * @return array<string, AuditContract>
     */
    public static function all(): array
    {
        if (self::$contracts !== null) {
            return self::$contracts;
        }

        $list = [
            new AuditContract(
                action: AuditAction::InternalAccountProvisioned,
                domain: AuditDomain::Account,
                allowedSubjectTypes: ['user'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::RoleChanged,
                domain: AuditDomain::Authorization,
                allowedSubjectTypes: ['user', 'role'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Flexible,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PermissionChanged,
                domain: AuditDomain::Authorization,
                allowedSubjectTypes: ['role', 'permission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Flexible,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::SubmissionCreated,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::SubmissionUpdated,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::SubmissionDocumentReplaced,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Flexible,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::SubmissionSubmitted,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::SubmissionResubmitted,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::SubmissionRevisionRequested,
                domain: AuditDomain::IntakeReview,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::SubmissionReadyForApproval,
                domain: AuditDomain::IntakeReview,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::SubmissionReturnedToStaff,
                domain: AuditDomain::IntakeDecision,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::SubmissionRejected,
                domain: AuditDomain::IntakeDecision,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::SubmissionDraftDeleted,
                domain: AuditDomain::Submission,
                allowedSubjectTypes: ['letter_submission'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Delete,
                positionAssignmentRequirement: PositionAssignmentRequirement::Forbidden,
            ),
            new AuditContract(
                action: AuditAction::LetterRegistered,
                domain: AuditDomain::Registration,
                allowedSubjectTypes: ['incoming_letter'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::LetterRouted,
                domain: AuditDomain::Routing,
                allowedSubjectTypes: ['letter_route'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::DispositionCreated,
                domain: AuditDomain::Disposition,
                allowedSubjectTypes: ['disposition'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::InstructionLabelCreated,
                domain: AuditDomain::WorkflowConfiguration,
                allowedSubjectTypes: ['instruction_label'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::InstructionLabelUpdated,
                domain: AuditDomain::WorkflowConfiguration,
                allowedSubjectTypes: ['instruction_label'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::InstructionLabelStatusChanged,
                domain: AuditDomain::WorkflowConfiguration,
                allowedSubjectTypes: ['instruction_label'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::DocumentVersionCreated,
                domain: AuditDomain::Document,
                allowedSubjectTypes: ['letter_document'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Required,
            ),
            new AuditContract(
                action: AuditAction::PositionAssigned,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position', 'position_assignment'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionHolderReplaced,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position', 'position_assignment'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionAssignmentEnded,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position', 'position_assignment'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionLevelCatalogSynchronized,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position_level_catalog'],
                requiresSubjectId: false,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::OrganizationalUnitCreated,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['organizational_unit'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::OrganizationalUnitUpdated,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['organizational_unit'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::OrganizationalUnitStatusChanged,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['organizational_unit'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionCreated,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Create,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionUpdated,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
            new AuditContract(
                action: AuditAction::PositionStatusChanged,
                domain: AuditDomain::Organization,
                allowedSubjectTypes: ['position'],
                requiresSubjectId: true,
                mutationType: AuditMutationType::Update,
                positionAssignmentRequirement: PositionAssignmentRequirement::Optional,
            ),
        ];

        self::$contracts = [];
        foreach ($list as $contract) {
            self::$contracts[$contract->action->value] = $contract;
        }

        return self::$contracts;
    }
}
