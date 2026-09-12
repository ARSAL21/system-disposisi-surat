<?php

namespace App\Actions;

use App\Enums\AccountType;
use App\Enums\DispositionRecipientStatus;
use App\Enums\RoleName;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GetAdminDashboardData
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Request $request): array
    {
        // 1. Sessions & Online Users
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
        $fifteenMinutesAgo = now()->subMinutes(15)->timestamp;

        $onlineCount = 0;
        $recentOnlineUsers = [];

        if (Schema::hasTable('sessions')) {
            $onlineCount = DB::table('sessions')
                ->where('last_activity', '>=', $fiveMinutesAgo)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id');

            $recentSessions = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->where('sessions.last_activity', '>=', $fifteenMinutesAgo)
                ->select(
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.account_type',
                    'sessions.last_activity',
                    'sessions.ip_address'
                )
                ->orderByDesc('sessions.last_activity')
                ->limit(10)
                ->get()
                ->unique('id')
                ->values()
                ->slice(0, 5)
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'account_type' => $u->account_type,
                    'last_seen' => Carbon::createFromTimestamp((int) $u->last_activity)->diffForHumans(),
                    'ip_address' => $u->ip_address,
                ]);

            $recentOnlineUsers = $recentSessions->all();
        }

        // 2. User Demographics & Accounts
        $totalUsers = User::count();
        $internalUsers = User::where('account_type', AccountType::InternalAccount->value)->count();
        $publicUsers = User::where('account_type', AccountType::PublicAccount->value)->count();
        $activeUsers = User::where('is_active', true)->count();
        $suspendedUsers = User::where('is_active', false)->count();

        $pendingInvitationsCount = 0;
        if (Schema::hasTable('user_invitations')) {
            $pendingInvitationsCount = DB::table('user_invitations')
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->where('expires_at', '>=', now())
                ->count();
        }

        // 3. Security & MFA Adoption
        $internalMfaEnabled = User::where('account_type', AccountType::InternalAccount->value)
            ->whereNotNull('two_factor_confirmed_at')
            ->count();

        $internalMfaPercentage = $internalUsers > 0
            ? (int) round(($internalMfaEnabled / $internalUsers) * 100)
            : 100;

        $superAdminRole = RoleName::SuperAdmin->value;
        $superAdminsCount = User::whereHas('roles', fn ($query) => $query->where('name', $superAdminRole))->count();
        $superAdminsWithoutMfa = User::whereHas('roles', fn ($query) => $query->where('name', $superAdminRole))
            ->whereNull('two_factor_confirmed_at')
            ->count();

        $passkeysCount = 0;
        if (Schema::hasTable('passkeys')) {
            $passkeysCount = DB::table('passkeys')->count();
        }

        // 4. Workflow & Letters Throughput
        $incomingLettersCount = 0;
        if (Schema::hasTable('incoming_letters')) {
            $incomingLettersCount = DB::table('incoming_letters')->count();
        }

        $activeDispositionsCount = 0;
        $completedDispositionsCount = 0;
        if (Schema::hasTable('disposition_recipients')) {
            $activeDispositionsCount = DB::table('disposition_recipients')
                ->whereIn('status', [DispositionRecipientStatus::Pending->value, DispositionRecipientStatus::InProgress->value])
                ->count();

            $completedDispositionsCount = DB::table('disposition_recipients')
                ->where('status', DispositionRecipientStatus::Completed->value)
                ->count();
        }

        $pendingSubmissionsCount = 0;
        if (Schema::hasTable('letter_submissions')) {
            $pendingSubmissionsCount = DB::table('letter_submissions')
                ->where('status', 'SUBMITTED')
                ->count();
        }

        // Top backlog / pending units in disposition
        $topPendingUnits = [];
        if (Schema::hasTable('disposition_recipients') && Schema::hasTable('positions') && Schema::hasTable('organizational_units')) {
            $topPendingUnits = DB::table('disposition_recipients')
                ->join('positions', 'disposition_recipients.recipient_position_id', '=', 'positions.id')
                ->join('organizational_units', 'positions.organizational_unit_id', '=', 'organizational_units.id')
                ->whereIn('disposition_recipients.status', [DispositionRecipientStatus::Pending->value, DispositionRecipientStatus::InProgress->value])
                ->select(
                    'organizational_units.id as unit_id',
                    'organizational_units.name as unit_name',
                    'organizational_units.code as unit_code',
                    'positions.id as position_id',
                    'positions.name as position_name',
                    DB::raw('count(*) as pending_count')
                )
                ->groupBy(
                    'organizational_units.id',
                    'organizational_units.name',
                    'organizational_units.code',
                    'positions.id',
                    'positions.name'
                )
                ->orderByDesc('pending_count')
                ->limit(4)
                ->get()
                ->map(fn ($r) => [
                    'unit_name' => $r->unit_name,
                    'unit_code' => $r->unit_code ?? '-',
                    'position_name' => $r->position_name,
                    'pending_count' => (int) $r->pending_count,
                ])
                ->all();
        }

        // 5. Recent System Audits & Events
        $recentEvents = [];
        if (Schema::hasTable('user_account_events')) {
            $events = DB::table('user_account_events')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(fn ($e) => [
                    'id' => $e->id,
                    'event_type' => $e->event_type,
                    'user_name' => $e->user_name ?? 'Sistem',
                    'user_email' => $e->user_email,
                    'description' => $e->description,
                    'created_at_human' => Carbon::parse($e->created_at)->diffForHumans(),
                ]);
            $recentEvents = $events->all();
        } elseif (Schema::hasTable('audit_logs')) {
            $logs = DB::table('audit_logs')
                ->leftJoin('users', 'audit_logs.actor_user_id', '=', 'users.id')
                ->select('audit_logs.*', 'users.name as actor_name')
                ->orderByDesc('audit_logs.created_at')
                ->limit(6)
                ->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'event_type' => $l->action,
                    'user_name' => $l->actor_name ?? 'Sistem',
                    'user_email' => null,
                    'description' => $l->action.' pada '.class_basename($l->subject_type),
                    'created_at_human' => Carbon::parse($l->created_at)->diffForHumans(),
                ]);
            $recentEvents = $logs->all();
        }

        // 6. Organization Overview
        $totalUnits = Schema::hasTable('organizational_units') ? DB::table('organizational_units')->count() : 0;
        $totalPositions = Schema::hasTable('positions') ? DB::table('positions')->count() : 0;
        $activeAssignments = Schema::hasTable('position_assignments')
            ? DB::table('position_assignments')->whereNull('ended_at')->count()
            : 0;

        return [
            'users' => [
                'online_count' => $onlineCount,
                'recent_online' => $recentOnlineUsers,
                'total' => $totalUsers,
                'internal_count' => $internalUsers,
                'public_count' => $publicUsers,
                'active_count' => $activeUsers,
                'suspended_count' => $suspendedUsers,
                'pending_invitations_count' => $pendingInvitationsCount,
            ],
            'security' => [
                'internal_mfa_enabled' => $internalMfaEnabled,
                'internal_mfa_percentage' => $internalMfaPercentage,
                'super_admins_count' => $superAdminsCount,
                'super_admins_without_mfa' => $superAdminsWithoutMfa,
                'passkeys_count' => $passkeysCount,
            ],
            'workflow' => [
                'incoming_letters_count' => $incomingLettersCount,
                'active_dispositions_count' => $activeDispositionsCount,
                'completed_dispositions_count' => $completedDispositionsCount,
                'pending_submissions_count' => $pendingSubmissionsCount,
                'top_pending_units' => $topPendingUnits,
            ],
            'recent_events' => $recentEvents,
            'organization' => [
                'units_count' => $totalUnits,
                'positions_count' => $totalPositions,
                'active_assignments_count' => $activeAssignments,
            ],
        ];
    }
}
