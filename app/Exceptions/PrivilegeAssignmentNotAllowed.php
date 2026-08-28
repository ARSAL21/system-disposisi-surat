<?php

namespace App\Exceptions;

use RuntimeException;

class PrivilegeAssignmentNotAllowed extends RuntimeException
{
    public static function userNotFound(): self
    {
        return new self('No account was found for the supplied email address.');
    }

    public static function ineligibleAccount(): self
    {
        return new self('The target must be an active and verified internal account.');
    }

    public static function catalogNotSynchronized(): self
    {
        return new self('The super-admin role is not synchronized. Run authorization:sync first.');
    }

    public static function lastSuperAdmin(): self
    {
        return new self('The final super-admin assignment cannot be revoked.');
    }
}
