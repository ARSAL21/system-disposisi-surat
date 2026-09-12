<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\OutgoingLetterTemplate;
use App\Models\User;
use App\Services\StandaloneOutgoingPositionAssignmentResolver;
use Illuminate\Auth\Access\Response;

final class OutgoingLetterTemplatePolicy
{
    public function __construct(private readonly StandaloneOutgoingPositionAssignmentResolver $resolver) {}

    public function viewAny(User $user): Response
    {
        return $this->permission($user, PermissionName::ViewOutgoingTemplates)
            ?? ($this->resolver->hasAnyStandaloneScope($user) ? Response::allow() : Response::denyAsNotFound());
    }

    public function view(User $user, OutgoingLetterTemplate $template): Response
    {
        $permission = $this->permission($user, PermissionName::ViewOutgoingTemplates);
        if ($permission instanceof Response) {
            return $permission;
        }

        return $this->resolver->hasStaffAssignmentForUnit($user, $template->organizational_unit_id)
            || $this->resolver->hasSectionHeadAssignmentForUnit($user, $template->organizational_unit_id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function create(User $user): Response
    {
        return $this->permission($user, PermissionName::ManageOutgoingTemplates)
            ?? ($this->resolver->sectionHeadUnitIds($user) !== [] ? Response::allow() : Response::denyAsNotFound());
    }

    public function manage(User $user, OutgoingLetterTemplate $template): Response
    {
        $permission = $this->permission($user, PermissionName::ManageOutgoingTemplates);
        if ($permission instanceof Response) {
            return $permission;
        }

        return $this->resolver->hasSectionHeadAssignmentForUnit($user, $template->organizational_unit_id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    private function permission(User $user, PermissionName $permission): ?Response
    {
        if (! $user->isInternalAccount() || ! $user->is_active || ! $user->hasVerifiedEmail()) {
            return Response::denyAsNotFound();
        }

        return $user->can($permission->value) ? null : Response::deny('Anda tidak memiliki hak akses surat keluar mandiri.');
    }
}
