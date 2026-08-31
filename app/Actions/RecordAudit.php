<?php

namespace App\Actions;

use App\Auditing\Guards\AuditLogGuard;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Support\Str;

class RecordAudit
{
    public function __construct(
        private readonly AuditLogGuard $guard = new AuditLogGuard,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(
        ?User $actor,
        AuditAction $action,
        string $subjectType,
        ?int $subjectId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?string $requestId = null,
        ?PositionAssignment $actorPositionAssignment = null,
    ): AuditLog {
        $resolvedRequestId = $requestId ?? Str::uuid()->toString();

        $this->guard->validate(
            actor: $actor,
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata,
            requestId: $resolvedRequestId,
            actorPositionAssignment: $actorPositionAssignment,
        );

        $request = app()->runningInConsole() ? null : request();

        $auditLog = new AuditLog;
        $auditLog->actor_user_id = $actor?->getKey();
        $auditLog->actor_position_assignment_id = $actorPositionAssignment?->getKey();
        $auditLog->action = $action->value;
        $auditLog->subject_type = $subjectType;
        $auditLog->subject_id = $subjectId;
        $auditLog->old_values = $oldValues;
        $auditLog->new_values = $newValues;
        $auditLog->metadata = $metadata;
        $auditLog->request_id = $resolvedRequestId;
        $auditLog->ip_address = $request?->ip();
        $auditLog->user_agent = $request?->userAgent();
        $auditLog->save();

        return $auditLog;
    }
}
