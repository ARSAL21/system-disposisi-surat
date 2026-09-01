<?php

use App\Authorization\AuthorizationCatalog;
use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\PositionAssignmentTestData;

uses(RefreshDatabase::class);

function roleWithPermissions(string $name, PermissionName ...$permissionNames): Role
{
    $permissions = array_map(
        static fn (PermissionName $permission): Permission => Permission::findOrCreate(
            $permission->value,
            AuthorizationCatalog::GUARD_NAME,
        ),
        $permissionNames,
    );

    $role = Role::findOrCreate($name, AuthorizationCatalog::GUARD_NAME);
    $role->syncPermissions($permissions);

    return $role;
}

test('guest and public accounts receive no internal capabilities', function (): void {
    $this->get(route('back-office.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_authorization', false)
            ->where('auth.capabilities.can_manage_authorization', false)
            ->where('auth.capabilities.can_view_organization', false)
            ->where('auth.capabilities.can_manage_organization', false)
            ->where('auth.capabilities.can_manage_position_assignments', false)
            ->where('auth.capabilities.can_view_privilege_audits', false)
            ->where('auth.capabilities.can_view_letter_activities', false)
            ->where('auth.capabilities.can_view_document_versions', false)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->where('auth.capabilities.can_view_dispositions', false)
            ->where('auth.capabilities.can_create_dispositions', false)
            ->where('auth.capabilities.can_view_disposition_instructions', false)
            ->where('auth.capabilities.can_manage_disposition_instructions', false),
        );

    $publicUser = User::factory()->create();
    $publicUser->assignRole(roleWithPermissions(
        'public-boundary-test',
        PermissionName::ViewAuthorization,
        PermissionName::ManageAuthorization,
        PermissionName::ViewOrganization,
        PermissionName::ManageOrganization,
        PermissionName::ManagePositionAssignments,
        PermissionName::ViewPrivilegeAudits,
    ));

    $this->actingAs($publicUser)
        ->get(route('public.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_authorization', false)
            ->where('auth.capabilities.can_manage_authorization', false)
            ->where('auth.capabilities.can_view_organization', false)
            ->where('auth.capabilities.can_manage_organization', false)
            ->where('auth.capabilities.can_manage_position_assignments', false)
            ->where('auth.capabilities.can_view_privilege_audits', false)
            ->where('auth.capabilities.can_view_letter_activities', false)
            ->where('auth.capabilities.can_view_document_versions', false)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->where('auth.capabilities.can_view_dispositions', false)
            ->where('auth.capabilities.can_create_dispositions', false)
            ->where('auth.capabilities.can_view_disposition_instructions', false)
            ->where('auth.capabilities.can_manage_disposition_instructions', false)
            ->missing('auth.user.roles')
            ->missing('auth.user.permissions'),
        );
});

test('internal capabilities are derived from explicit permissions', function (): void {
    $unprivilegedUser = User::factory()->internal()->create();

    $this->actingAs($unprivilegedUser)
        ->get(route('back-office.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_authorization', false)
            ->where('auth.capabilities.can_manage_authorization', false)
            ->where('auth.capabilities.can_view_organization', false)
            ->where('auth.capabilities.can_manage_organization', false)
            ->where('auth.capabilities.can_manage_position_assignments', false)
            ->where('auth.capabilities.can_view_privilege_audits', false)
            ->where('auth.capabilities.can_view_letter_activities', false)
            ->where('auth.capabilities.can_view_document_versions', false)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->where('auth.capabilities.can_view_dispositions', false)
            ->where('auth.capabilities.can_create_dispositions', false)
            ->where('auth.capabilities.can_view_disposition_instructions', false)
            ->where('auth.capabilities.can_manage_disposition_instructions', false),
        );

    $viewer = User::factory()->internal()->create();
    $viewer->assignRole(roleWithPermissions(
        'authorization-viewer',
        PermissionName::ViewAuthorization,
    ));

    $this->actingAs($viewer)
        ->get(route('back-office.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_authorization', true)
            ->where('auth.capabilities.can_manage_authorization', false)
            ->where('auth.capabilities.can_view_organization', false)
            ->where('auth.capabilities.can_manage_organization', false)
            ->where('auth.capabilities.can_manage_position_assignments', false)
            ->where('auth.capabilities.can_view_privilege_audits', false)
            ->where('auth.capabilities.can_view_letter_activities', false)
            ->where('auth.capabilities.can_view_document_versions', false)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->where('auth.capabilities.can_view_dispositions', false)
            ->where('auth.capabilities.can_create_dispositions', false)
            ->where('auth.capabilities.can_view_disposition_instructions', false)
            ->where('auth.capabilities.can_manage_disposition_instructions', false)
            ->missing('auth.user.roles')
            ->missing('auth.user.permissions'),
        );
});

test('authorization skeleton routes enforce account and permission boundaries', function (): void {
    $publicUser = User::factory()->create();
    $publicUser->assignRole(roleWithPermissions(
        'public-authorization-viewer',
        PermissionName::ViewAuthorization,
    ));

    $this->actingAs($publicUser)
        ->get(route('back-office.authorization.index'))
        ->assertNotFound();

    $internalUser = User::factory()->internal()->create();

    $this->actingAs($internalUser)
        ->get(route('back-office.authorization.index'))
        ->assertForbidden();

    $viewer = User::factory()->internal()->create();
    $viewer->assignRole(roleWithPermissions(
        'internal-authorization-viewer',
        PermissionName::ViewAuthorization,
    ));

    $this->actingAs($viewer)
        ->get(route('back-office.authorization.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/authorization/Index'),
        );

    $this->actingAs($viewer)
        ->get(route('back-office.privilege-audits.index'))
        ->assertForbidden();
});

test('critical administrator capabilities remain protected by MFA', function (): void {
    $administrator = User::factory()->internal()->create();
    PositionAssignmentTestData::grantSuperAdminRole($administrator);

    $this->actingAs($administrator)
        ->get(route('back-office.authorization.index'))
        ->assertRedirect(route('security.edit'));

    $this->actingAs($administrator)
        ->get(route('back-office.privilege-audits.index'))
        ->assertRedirect(route('security.edit'));

    $mfaAdministrator = User::factory()->internal()->withTwoFactor()->create();
    PositionAssignmentTestData::grantSuperAdminRole($mfaAdministrator);

    $this->actingAs($mfaAdministrator)
        ->get(route('back-office.authorization.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.capabilities.can_view_authorization', true)
            ->where('auth.capabilities.can_manage_authorization', true)
            ->where('auth.capabilities.can_view_organization', true)
            ->where('auth.capabilities.can_manage_organization', true)
            ->where('auth.capabilities.can_manage_position_assignments', true)
            ->where('auth.capabilities.can_view_privilege_audits', true)
            ->where('auth.capabilities.can_view_letter_activities', true)
            ->where('auth.capabilities.can_view_document_versions', false)
            ->where('auth.capabilities.can_view_letter_routing', false)
            ->where('auth.capabilities.can_create_letter_routing', false)
            ->where('auth.capabilities.can_view_executive_inbox', false)
            ->where('auth.capabilities.can_view_dispositions', false)
            ->where('auth.capabilities.can_create_dispositions', false)
            ->where('auth.capabilities.can_view_disposition_instructions', true)
            ->where('auth.capabilities.can_manage_disposition_instructions', true),
        );

    $this->actingAs($mfaAdministrator)
        ->get(route('back-office.privilege-audits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('back-office/privilege-audits/Index'));
});
