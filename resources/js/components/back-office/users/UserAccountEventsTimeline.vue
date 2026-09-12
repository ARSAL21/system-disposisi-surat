<script setup lang="ts">
import {
    Activity,
    Ban,
    CheckCircle2,
    KeyRound,
    LogOut,
    RefreshCw,
    Shield,
    ShieldAlert,
    UserCheck,
    UserPlus,
} from '@lucide/vue';
import type {
    UserAccountEventItem,
    UserAccountEventType,
} from '@/types/user-management';

defineProps<{
    events: UserAccountEventItem[];
}>();

function formatDate(isoString: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoString));
}

function getEventBadge(eventType: UserAccountEventType) {
    switch (eventType) {
        case 'INVITATION_CREATED':
            return {
                label: 'Undangan Dibuat',
                icon: UserPlus,
                color: 'text-primary bg-primary/10',
            };
        case 'INVITATION_RESENT':
            return {
                label: 'Undangan Dikirim Ulang',
                icon: RefreshCw,
                color: 'text-blue-600 bg-blue-50 dark:bg-blue-950/30',
            };
        case 'INVITATION_ACCEPTED':
            return {
                label: 'Undangan Diterima',
                icon: CheckCircle2,
                color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30',
            };
        case 'INVITATION_REVOKED':
            return {
                label: 'Undangan Dicabut',
                icon: Ban,
                color: 'text-slate-600 bg-slate-100 dark:bg-slate-800',
            };
        case 'USER_DEACTIVATED':
            return {
                label: 'Akun Dinonaktifkan',
                icon: Ban,
                color: 'text-destructive bg-destructive/10',
            };
        case 'USER_REACTIVATED':
            return {
                label: 'Akun Diaktifkan',
                icon: UserCheck,
                color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30',
            };
        case 'SESSIONS_REVOKED':
            return {
                label: 'Sesi Dicabut',
                icon: LogOut,
                color: 'text-amber-600 bg-amber-50 dark:bg-amber-950/30',
            };
        case 'PASSWORD_RESET_LINK_SENT':
            return {
                label: 'Tautan Reset Terkirim',
                icon: KeyRound,
                color: 'text-indigo-600 bg-indigo-50 dark:bg-indigo-950/30',
            };
        case 'MFA_RESET':
            return {
                label: 'Reset MFA',
                icon: ShieldAlert,
                color: 'text-destructive bg-destructive/10',
            };
        case 'ROLES_DETACHED':
            return {
                label: 'Role Dilepas',
                icon: Shield,
                color: 'text-amber-600 bg-amber-50 dark:bg-amber-950/30',
            };
        default:
            return {
                label: eventType,
                icon: Activity,
                color: 'text-muted-foreground bg-muted',
            };
    }
}
</script>

<template>
    <div class="rounded-2xl border bg-card p-5 shadow-xs">
        <div class="mb-5 flex items-center gap-2.5">
            <span class="rounded-xl bg-primary/10 p-2 text-primary">
                <Shield class="size-5" />
            </span>
            <div>
                <h3 class="text-sm font-bold text-foreground">
                    Riwayat Peristiwa & Keamanan Akun
                </h3>
                <p class="text-xs text-muted-foreground">
                    Catatan audit append-only resmi atas seluruh mutasi akun
                </p>
            </div>
        </div>

        <div
            v-if="events.length === 0"
            class="rounded-xl border border-dashed p-6 text-center text-xs text-muted-foreground"
        >
            Belum ada peristiwa keamanan yang tercatat pada akun ini.
        </div>

        <div
            v-else
            class="relative space-y-6 pl-6 before:absolute before:top-2 before:bottom-2 before:left-2.5 before:w-0.5 before:bg-border/60"
        >
            <div v-for="event in events" :key="event.id" class="group relative">
                <!-- Dot Icon -->
                <span
                    class="absolute top-0.5 -left-6 flex size-5 items-center justify-center rounded-full border-2 border-background text-xs"
                    :class="getEventBadge(event.event_type).color"
                >
                    <component
                        :is="getEventBadge(event.event_type).icon"
                        class="size-3"
                    />
                </span>

                <div
                    class="rounded-xl border bg-muted/25 p-3.5 transition-colors hover:bg-muted/40"
                >
                    <div
                        class="mb-1.5 flex flex-col justify-between gap-1 sm:flex-row sm:items-center"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                :class="getEventBadge(event.event_type).color"
                            >
                                {{ getEventBadge(event.event_type).label }}
                            </span>
                            <span
                                v-if="event.actor"
                                class="text-xs text-muted-foreground"
                            >
                                oleh
                                <strong class="text-foreground">{{
                                    event.actor.name
                                }}</strong>
                            </span>
                        </div>
                        <time
                            class="text-[11px] text-muted-foreground tabular-nums"
                        >
                            {{ formatDate(event.created_at) }}
                        </time>
                    </div>

                    <p class="text-xs leading-relaxed text-foreground">
                        {{ event.description }}
                    </p>

                    <div
                        v-if="event.reason"
                        class="mt-2 rounded-r-md border-l-2 border-primary/40 bg-muted/30 py-0.5 pl-2.5 text-xs text-muted-foreground italic"
                    >
                        Alasan: "{{ event.reason }}"
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
