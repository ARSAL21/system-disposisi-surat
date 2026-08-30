<script setup lang="ts">
import { ArrowUpRight, FileKey2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatLetterActivityTime,
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
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full border-collapse text-left text-sm">
            <caption class="sr-only">
                Daftar aktivitas surat pada rentang waktu terpilih
            </caption>
            <thead>
                <tr
                    class="border-b bg-slate-50/80 text-xs tracking-wide text-slate-500 uppercase dark:bg-slate-900/50 dark:text-slate-400"
                >
                    <th scope="col" class="px-5 py-3.5 font-medium">Waktu</th>
                    <th scope="col" class="px-5 py-3.5 font-medium">
                        Aktivitas
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-medium">Surat</th>
                    <th scope="col" class="px-5 py-3.5 font-medium">
                        Pelaksana
                    </th>
                    <th scope="col" class="px-5 py-3.5 text-right font-medium">
                        Detail
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="activity in activities"
                    :key="activity.id"
                    class="transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-900/40"
                >
                    <td class="whitespace-nowrap px-5 py-4 align-top">
                        <p class="font-semibold text-slate-900 tabular-nums dark:text-white">
                            {{
                                formatLetterActivityTime(
                                    activity.occurred_at,
                                    timezone,
                                )
                            }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">WITA</p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <Badge
                            variant="outline"
                            :class="letterActivityActionClass(activity.action)"
                        >
                            {{ letterActivityActionLabels[activity.action] }}
                        </Badge>
                    </td>
                    <td class="max-w-sm px-5 py-4 align-top">
                        <template
                            v-if="visibility === 'details' && activity.target"
                        >
                            <p
                                class="line-clamp-2 font-medium text-slate-900 dark:text-white"
                            >
                                {{ activity.target.subject }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    activity.target.agenda_number ??
                                    activity.target.public_id
                                }}
                                · {{ activity.target.sender }}
                            </p>
                        </template>
                        <div v-else class="flex items-center gap-2 text-muted-foreground">
                            <FileKey2 class="size-4" aria-hidden="true" />
                            <span>Identitas surat dilindungi</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ activity.actor.name }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{
                                activity.actor.position ??
                                'Pemohon melalui portal publik'
                            }}
                        </p>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="min-h-9"
                            :aria-label="`Lihat detail ${letterActivityActionLabels[activity.action]}`"
                            @click="$emit('detail', activity)"
                        >
                            Lihat
                            <ArrowUpRight class="size-4" aria-hidden="true" />
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
