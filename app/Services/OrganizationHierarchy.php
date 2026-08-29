<?php

namespace App\Services;

use App\Exceptions\OrganizationNotAllowed;
use App\Models\OrganizationalUnit;

class OrganizationHierarchy
{
    public function ensureValidParent(?OrganizationalUnit $parent, ?OrganizationalUnit $unit = null): void
    {
        if ($parent === null) {
            return;
        }

        if (! $parent->is_active) {
            throw OrganizationNotAllowed::inactiveParent();
        }

        if ($unit === null) {
            return;
        }

        $ancestor = $parent;

        while ($ancestor !== null) {
            if ($ancestor->is($unit)) {
                throw OrganizationNotAllowed::hierarchyCycle();
            }

            $ancestor = $ancestor->parent()->first();
        }
    }
}
