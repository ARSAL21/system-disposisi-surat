<?php

namespace App\Enums;

enum UserAccountEventType: string
{
    case InvitationCreated = 'INVITATION_CREATED';
    case InvitationResent = 'INVITATION_RESENT';
    case InvitationAccepted = 'INVITATION_ACCEPTED';
    case InvitationRevoked = 'INVITATION_REVOKED';
    case UserDeactivated = 'USER_DEACTIVATED';
    case UserReactivated = 'USER_REACTIVATED';
    case SessionsRevoked = 'SESSIONS_REVOKED';
    case PasswordResetLinkSent = 'PASSWORD_RESET_LINK_SENT';
    case MfaReset = 'MFA_RESET';
    case RolesDetached = 'ROLES_DETACHED';
    case UserLoggedIn = 'USER_LOGGED_IN';
}
