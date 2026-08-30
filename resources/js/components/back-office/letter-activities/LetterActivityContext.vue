<script setup lang="ts">
import { Clock3, FileKey2, UserRound } from '@lucide/vue';
import { formatLetterActivityDateTime } from '@/lib/letterActivityPresentation';
import type {
    LetterActivityRecord,
    LetterActivityVisibility,
} from '@/types';

defineProps<{
    activity: LetterActivityRecord;
    timezone: string;
    visibility: LetterActivityVisibility;
}>();
</script>

<template>
    <section class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border p-4">
            <div class="flex items-start gap-3">
                <UserRound
                    class="mt-0.5 size-5 text-indigo-600 dark:text-indigo-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Pelaksana
                    </p>
                    <p class="mt-1 font-semibold text-slate-950 dark:text-white">
                        {{ activity.actor.name }}
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{
                            activity.actor.position ??
                            'Pemohon melalui portal publik'
                        }}
                        <template v-if="activity.actor.unit">
                            · {{ activity.actor.unit }}
                        </template>
                    </p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border p-4">
            <div class="flex items-start gap-3">
                <Clock3
                    class="mt-0.5 size-5 text-indigo-600 dark:text-indigo-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Waktu aktivitas
                    </p>
                    <p class="mt-1 font-semibold text-slate-950 dark:text-white">
                        {{
                            formatLetterActivityDateTime(
                                activity.occurred_at,
                                timezone,
                            )
                        }}
                        WITA
                    </p>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Zona waktu kantor
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section
        v-if="visibility === 'details' && activity.target"
        class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900 dark:bg-blue-950/20"
    >
        <div class="flex items-start gap-3">
            <FileKey2
                class="mt-0.5 size-5 text-blue-700 dark:text-blue-300"
                aria-hidden="true"
            />
            <div class="min-w-0">
                <p class="text-xs font-medium text-muted-foreground">
                    Surat terkait
                </p>
                <h3 class="mt-1 font-semibold text-slate-950 dark:text-white">
                    {{ activity.target.subject }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ activity.target.sender }} ·
                    {{
                        activity.target.agenda_number ?? activity.target.public_id
                    }}
                </p>
            </div>
        </div>
    </section>
</template>
