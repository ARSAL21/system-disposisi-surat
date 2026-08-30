<?php

namespace App\Exceptions;

use LogicException;

final class AuditLogMutationDenied extends LogicException
{
    public static function forAppendOnlyRecord(): self
    {
        return new self('Audit logs are append-only. Existing records cannot be changed or deleted.');
    }
}
