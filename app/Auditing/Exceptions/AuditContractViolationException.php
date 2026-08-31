<?php

namespace App\Auditing\Exceptions;

use LogicException;

class AuditContractViolationException extends LogicException
{
    public static function forAction(string $action, string $reason): self
    {
        return new self(sprintf('Audit contract violation for action [%s]: %s', $action, $reason));
    }
}
