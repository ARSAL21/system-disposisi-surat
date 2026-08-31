<?php

namespace App\Auditing\Guards;

use App\Auditing\Contracts\AuditActionContractRegistry;
use App\Auditing\Contracts\AuditContract;
use App\Auditing\Enums\AuditMutationType;
use App\Auditing\Enums\PositionAssignmentRequirement;
use App\Auditing\Exceptions\AuditContractViolationException;
use App\Enums\AuditAction;
use App\Models\PositionAssignment;
use App\Models\User;

class AuditLogGuard
{
    public function __construct(
        private readonly AuditSecretScanner $secretScanner = new AuditSecretScanner,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function validate(
        ?User $actor,
        AuditAction $action,
        string $subjectType,
        ?int $subjectId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $requestId = null,
        ?PositionAssignment $actorPositionAssignment = null,
    ): void {
        $contract = AuditActionContractRegistry::get($action);

        $this->validateSubject($contract, $subjectType, $subjectId);
        $this->validateMutationPayload($contract, $oldValues, $newValues);
        $this->validateActorAndContext($contract, $actor, $metadata);
        $this->validatePositionAssignment($contract, $actor, $actorPositionAssignment);
        $this->validateRequestId($contract, $requestId);

        $this->secretScanner->scan($contract, 'old_values', $oldValues);
        $this->secretScanner->scan($contract, 'new_values', $newValues);
        $this->secretScanner->scan($contract, 'metadata', $metadata);
    }

    private function validateSubject(AuditContract $contract, string $subjectType, ?int $subjectId): void
    {
        if (! $contract->allowsSubjectType($subjectType)) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                sprintf(
                    'Subject type [%s] is not allowed. Expected one of: [%s].',
                    $subjectType,
                    implode(', ', $contract->allowedSubjectTypes),
                ),
            );
        }

        if ($contract->requiresSubjectId && $subjectId === null) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                'Subject ID is required for this action.',
            );
        }

        if (! $contract->requiresSubjectId && $subjectId !== null) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                'Subject ID must be null for this action.',
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function validateMutationPayload(
        AuditContract $contract,
        ?array $oldValues,
        ?array $newValues,
    ): void {
        $hasOld = $oldValues !== null && $oldValues !== [];
        $hasNew = $newValues !== null && $newValues !== [];

        match ($contract->mutationType) {
            AuditMutationType::Create => (function () use ($contract, $hasOld, $hasNew): void {
                if ($hasOld) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Create mutation must not have old_values.',
                    );
                }
                if (! $hasNew) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Create mutation requires non-empty new_values.',
                    );
                }
            })(),
            AuditMutationType::Update => (function () use ($contract, $hasOld, $hasNew): void {
                if (! $hasOld) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Update mutation requires non-empty old_values.',
                    );
                }
                if (! $hasNew) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Update mutation requires non-empty new_values.',
                    );
                }
            })(),
            AuditMutationType::Delete => (function () use ($contract, $hasOld, $hasNew): void {
                if (! $hasOld) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Delete mutation requires non-empty old_values.',
                    );
                }
                if ($hasNew) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Delete mutation must not have new_values.',
                    );
                }
            })(),
            AuditMutationType::Flexible => (function () use ($contract, $hasOld, $hasNew): void {
                if (! $hasOld && ! $hasNew) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Flexible mutation requires at least one of old_values or new_values to be non-empty.',
                    );
                }
            })(),
        };
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function validateActorAndContext(
        AuditContract $contract,
        ?User $actor,
        ?array $metadata,
    ): void {
        if ($actor === null) {
            if (! app()->runningInConsole()) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'An unauthenticated audit actor is only allowed for console operations.',
                );
            }

            $source = $metadata['source'] ?? null;
            $command = $metadata['command'] ?? null;

            if ($source !== 'console' || ! is_string($command) || trim($command) === '') {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Unauthenticated console audit requires metadata with source="console" and non-empty "command".',
                );
            }

            if (strlen($command) > 64 || ! preg_match('/^[a-z0-9]+(?:[-:][a-z0-9]+)*$/', $command)) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Console command name must be a valid command identifier (e.g. authorization:sync, organization:sync-levels) without arguments.',
                );
            }
        }
    }

    private function validatePositionAssignment(
        AuditContract $contract,
        ?User $actor,
        ?PositionAssignment $actorPositionAssignment,
    ): void {
        if ($contract->positionAssignmentRequirement === PositionAssignmentRequirement::Required) {
            if ($actor === null) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Acting user is required for actions demanding a Position Assignment.',
                );
            }

            if ($actorPositionAssignment === null) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Active Position Assignment is required for this action.',
                );
            }

            $this->ensureValidPersistedAssignment($contract, $actor, $actorPositionAssignment);
        } elseif ($contract->positionAssignmentRequirement === PositionAssignmentRequirement::Forbidden) {
            if ($actorPositionAssignment !== null) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Position Assignment is forbidden for this action.',
                );
            }
        } else {
            // Optional
            if ($actorPositionAssignment !== null) {
                if ($actor === null) {
                    throw AuditContractViolationException::forAction(
                        $contract->action->value,
                        'Acting user is required when a Position Assignment is provided.',
                    );
                }

                $this->ensureValidPersistedAssignment($contract, $actor, $actorPositionAssignment);
            }
        }
    }

    private function ensureValidPersistedAssignment(
        AuditContract $contract,
        User $actor,
        PositionAssignment $assignment,
    ): void {
        $assignmentId = $assignment->getKey();

        if (! $assignment->exists || $assignmentId === null) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                'The provided Position Assignment must be a persisted database record.',
            );
        }

        $isValidActiveAssignment = PositionAssignment::query()
            ->whereKey($assignmentId)
            ->where('user_id', $actor->getKey())
            ->whereNotNull('started_at')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->exists();

        if (! $isValidActiveAssignment) {
            throw AuditContractViolationException::forAction(
                $contract->action->value,
                'The provided Position Assignment is not an active, started database record belonging to the acting user.',
            );
        }
    }

    private function validateRequestId(AuditContract $contract, ?string $requestId): void
    {
        if ($requestId !== null) {
            if (strlen($requestId) < 1 || strlen($requestId) > 64) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Request ID must have a length between 1 and 64 characters.',
                );
            }

            if (! preg_match('/^[A-Za-z0-9_\-:.]+$/', $requestId)) {
                throw AuditContractViolationException::forAction(
                    $contract->action->value,
                    'Request ID contains invalid characters. Only alphanumeric and [_-:.] are allowed.',
                );
            }
        }
    }
}
