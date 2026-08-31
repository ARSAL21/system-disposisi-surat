<?php

namespace App\Auditing\Contracts;

use App\Auditing\Enums\AuditDomain;
use App\Auditing\Enums\AuditMutationType;
use App\Auditing\Enums\PositionAssignmentRequirement;
use App\Enums\AuditAction;

final readonly class AuditContract
{
    /**
     * @param  array<string>  $allowedSubjectTypes
     */
    public function __construct(
        public AuditAction $action,
        public AuditDomain $domain,
        public array $allowedSubjectTypes,
        public bool $requiresSubjectId,
        public AuditMutationType $mutationType,
        public PositionAssignmentRequirement $positionAssignmentRequirement,
    ) {}

    public function allowsSubjectType(string $subjectType): bool
    {
        return in_array($subjectType, $this->allowedSubjectTypes, true);
    }
}
