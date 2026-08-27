<?php

namespace App\Actions;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
use LogicException;

class RecordAudit
{
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
    ): AuditLog {
        if ($actor === null && ! app()->runningInConsole()) {
            throw new LogicException('An unauthenticated audit actor is only allowed for console operations.');
        }

        $request = app()->runningInConsole() ? null : request();

        $auditLog = new AuditLog;
        $auditLog->actor_user_id = $actor?->getKey();
        $auditLog->actor_position_assignment_id = null;
        $auditLog->action = $action->value;
        $auditLog->subject_type = $subjectType;
        $auditLog->subject_id = $subjectId;
        $auditLog->old_values = $oldValues;
        $auditLog->new_values = $newValues;
        $auditLog->metadata = $metadata;
        $auditLog->request_id = $requestId ?? Str::uuid()->toString();
        $auditLog->ip_address = $request?->ip();
        $auditLog->user_agent = $request?->userAgent();
        $auditLog->save();

        return $auditLog;
    }
}
