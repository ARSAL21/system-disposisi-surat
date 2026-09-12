<?php

namespace App\Http\Controllers\BackOffice\UserManagement;

use App\Actions\UserManagement\FormatUserManagementItem;
use App\Enums\AccountType;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccountEvent;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request, FormatUserManagementItem $formatter): Response
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can(PermissionName::ViewUsers->value), 403);

        $users = User::query()
            ->with([
                'roles',
                'activePositionAssignments.position.positionLevel',
                'activePositionAssignments.position.organizationalUnit',
                'positionAssignments.position.positionLevel',
                'positionAssignments.position.organizationalUnit',
            ])
            ->latest()
            ->get()
            ->map(fn (User $user) => $formatter->execute($user, $actor))
            ->values()
            ->all();

        $invitations = UserInvitation::query()
            ->with('invitedBy')
            ->latest()
            ->get()
            ->map(function (UserInvitation $invitation) {
                $status = 'PENDING';
                if ($invitation->accepted_at !== null) {
                    $status = 'ACCEPTED';
                } elseif ($invitation->revoked_at !== null) {
                    $status = 'REVOKED';
                } elseif ($invitation->expires_at < now()) {
                    $status = 'EXPIRED';
                }

                return [
                    'id' => $invitation->getKey(),
                    'public_id' => $invitation->public_id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'phone_number' => $invitation->phone_number,
                    'account_type' => $invitation->account_type === AccountType::InternalAccount ? 'INTERNAL' : 'PUBLIC',
                    'status' => $status,
                    'expires_at' => $invitation->expires_at->toISOString(),
                    'accepted_at' => $invitation->accepted_at?->toISOString(),
                    'revoked_at' => $invitation->revoked_at?->toISOString(),
                    'created_at' => $invitation->created_at?->toISOString() ?? now()->toISOString(),
                    'invited_by' => [
                        'id' => $invitation->invitedBy->getKey(),
                        'name' => $invitation->invitedBy->name,
                        'email' => $invitation->invitedBy->email,
                    ],
                ];
            })
            ->values()
            ->all();

        $accountEvents = UserAccountEvent::query()
            ->with('actor')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (UserAccountEvent $event) {
                return [
                    'id' => $event->getKey(),
                    'user_id' => $event->user_id,
                    'user_name' => $event->user_name,
                    'user_email' => $event->user_email,
                    'event_type' => $event->event_type->value,
                    'actor' => $event->actor ? [
                        'id' => $event->actor->getKey(),
                        'name' => $event->actor->name,
                        'email' => $event->actor->email,
                    ] : null,
                    'description' => $event->description,
                    'reason' => $event->reason,
                    'metadata' => $event->metadata,
                    'created_at' => $event->created_at->toISOString(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('back-office/users/Index', [
            'users' => $users,
            'invitations' => $invitations,
            'accountEvents' => $accountEvents,
            'capabilities' => [
                'can_view_users' => true,
                'can_invite_users' => $actor->can(PermissionName::InviteUsers->value),
                'can_manage_user_status' => $actor->can(PermissionName::ManageUserStatus->value),
                'can_manage_user_security' => $actor->can(PermissionName::ManageUserSecurity->value),
            ],
            'preview' => false,
        ]);
    }

    public function show(Request $request, User $managedUser, FormatUserManagementItem $formatter): Response
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->can('view', $managedUser), 403);

        $managedUser->load([
            'roles',
            'activePositionAssignments.position.positionLevel',
            'activePositionAssignments.position.organizationalUnit',
            'positionAssignments.position.positionLevel',
            'positionAssignments.position.organizationalUnit',
        ]);

        $userData = $formatter->execute($managedUser, $actor);

        $accountEvents = UserAccountEvent::query()
            ->with('actor')
            ->where(function ($query) use ($managedUser) {
                $query->where('user_id', $managedUser->getKey())
                    ->orWhere('user_email', $managedUser->email);
            })
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(function (UserAccountEvent $event) {
                return [
                    'id' => $event->getKey(),
                    'user_id' => $event->user_id,
                    'user_name' => $event->user_name,
                    'user_email' => $event->user_email,
                    'event_type' => $event->event_type->value,
                    'actor' => $event->actor ? [
                        'id' => $event->actor->getKey(),
                        'name' => $event->actor->name,
                        'email' => $event->actor->email,
                    ] : null,
                    'description' => $event->description,
                    'reason' => $event->reason,
                    'metadata' => $event->metadata,
                    'created_at' => $event->created_at->toISOString(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('back-office/users/Show', [
            'user' => $userData,
            'accountEvents' => $accountEvents,
            'capabilities' => [
                'can_view_users' => true,
                'can_invite_users' => $actor->can(PermissionName::InviteUsers->value),
                'can_manage_user_status' => $actor->can('manageStatus', $managedUser),
                'can_manage_user_security' => $actor->can('manageSecurity', $managedUser),
            ],
            'preview' => false,
        ]);
    }
}
