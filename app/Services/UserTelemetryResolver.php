<?php

namespace App\Services;

use App\Enums\UserAccountEventType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserTelemetryResolver
{
    /**
     * @return array{
     *     last_login_at: string|null,
     *     last_login_ip: string|null,
     *     last_login_device: string|null,
     *     active_sessions_count: int
     * }
     */
    public function resolve(User $user): array
    {
        $sessionsQuery = DB::table('sessions')->where('user_id', $user->getKey());
        $activeSessionsCount = (int) $sessionsQuery->count();
        $latestSession = $sessionsQuery->orderByDesc('last_activity')->first();

        $latestLoginEvent = $user->accountEvents()
            ->where('event_type', UserAccountEventType::UserLoggedIn)
            ->latest('created_at')
            ->first();

        $lastLoginAt = null;
        $lastLoginIp = null;
        $rawUserAgent = null;

        if ($latestSession && $latestSession->last_activity) {
            $lastLoginAt = Carbon::createFromTimestamp($latestSession->last_activity)->toISOString();
            $lastLoginIp = $latestSession->ip_address;
            $rawUserAgent = $latestSession->user_agent;
        } elseif ($latestLoginEvent) {
            $lastLoginAt = $latestLoginEvent->created_at->toISOString();
            $metadata = is_array($latestLoginEvent->metadata) ? $latestLoginEvent->metadata : [];
            $lastLoginIp = $metadata['ip_address'] ?? null;
            $rawUserAgent = $metadata['user_agent'] ?? null;
        }

        return [
            'last_login_at' => $lastLoginAt,
            'last_login_ip' => $lastLoginIp,
            'last_login_device' => $this->parseDeviceAndBrowser($rawUserAgent),
            'active_sessions_count' => $activeSessionsCount,
        ];
    }

    private function parseDeviceAndBrowser(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        // Platform detection
        $os = 'Perangkat Tidak Dikenal';
        if (str_contains($userAgent, 'Windows NT 10.0')) {
            $os = 'Windows 10/11';
        } elseif (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $os = 'iPhone';
        } elseif (str_contains($userAgent, 'iPad')) {
            $os = 'iPad';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        }

        // Browser detection
        $browser = 'Peramban Web';
        if (str_contains($userAgent, 'Edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium/')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($userAgent, 'Firefox/')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/')) {
            $browser = 'Apple Safari';
        } elseif (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/')) {
            $browser = 'Opera';
        }

        return "{$browser} di {$os}";
    }
}
