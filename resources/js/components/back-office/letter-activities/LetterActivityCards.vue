<script setup lang="ts">
import { ArrowUpRight, Clock3, FileKey2, UserRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatLetterActivityDateTime,
    letterActivityActionClass,
    letterActivityActionLabels,
} from '@/lib/letterActivityPresentation';
import type {
    LetterActivityRecord,
    LetterActivityVisibility,
} from '@/types';

defineProps<{
    activities: LetterActivityRecord[];
    timezone: string;
    visibility: LetterActivityVisibility;
}>();
defineEmits<{
    detail: [activity: LetterActivityRecord];
}>();
</script>

<template>
    <div class="divide-y lg:hidden">
        <article
            v-for="activity in activities"
            :key="activity.id"
            class="space-y-4 p-4 sm:p-5"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="letterActivityActionClass(activity.action)"
                >
                    {{ letterActivityActionLabels[activity.action] }}
                </Badge>
                <span
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Clock3 class="size-3.5" aria-hidden="true" />
                    {{
                        formatLetterActivityDateTime(
                            activity.occurred_at,
                            timezone,
                        )
                    }}
                    WITA
                </span>
            </div>

            <div v-if="visibility === 'details' && activity.target">
                <h3 class="font-semibold leading-6 text-slate-950 dark:text-white">
                    {{ activity.target.subject }}
                </h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        activity.target.agenda_number ?? activity.target.public_id
                    }}
                    · {{ activity.target.sender }}
                </p>
            </div>
            <div
                v-else
                class="flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2.5 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300"
            >
                <FileKey2 class="size-4" aria-hidden="true" />
                Identitas surat dilindungi
            </div>

            <div
                class="flex flex-col gap-3 border-t border-dashed pt-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-2.5">
                    <span
                        class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-700 dark:text-indigo-300"
                    >
                        <UserRound class="size-4" aria-hidden="true" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                            {{ activity.actor.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                activity.actor.position ??
                                'Pemohon melalui portal publik'
                            }}
                        </p>
                    </div>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11 sm:min-h-9"
                    @click="$emit('detail', activity)"
                >
                    Lihat detail
                    <ArrowUpRight class="size-4" aria-hidden="true" />
                </Button>
            </div>
        </article>
    </div>
</template>
