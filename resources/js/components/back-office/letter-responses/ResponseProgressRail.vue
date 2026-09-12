<script setup lang="ts">
import { Check, FileCheck2, FileText, Gavel, Send } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import type { LetterResponseDossier } from '@/types';

const props = defineProps<{ dossier: LetterResponseDossier }>();

const stages = [
    {
        label: 'Surat selesai',
        caption: 'Cabang teknis lengkap',
        icon: FileCheck2,
    },
    { label: 'Bahan teknis', caption: 'Lampiran per Bagian', icon: FileText },
    { label: 'Proposal Asisten', caption: 'Usulan balasan', icon: Send },
    { label: 'Mandat', caption: 'Siap diterbitkan', icon: Gavel },
];

const activeStage = computed(() => {
    if (props.dossier.mandates.length > 0) {
        return 4;
    }

    if (props.dossier.assistants.some((assistant) => assistant.proposal)) {
        return 3;
    }

    if (
        props.dossier.assistants.some((assistant) =>
            assistant.children.some((child) => child.material),
        )
    ) {
        return 2;
    }

    return props.dossier.progress.percent === 100 ? 1 : 0;
});
</script>

<template>
    <Card
        class="overflow-hidden border-indigo-100/80 bg-gradient-to-r from-indigo-50/70 via-background to-background dark:border-indigo-950 dark:from-indigo-950/30"
    >
        <CardContent class="p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-indigo-600 uppercase dark:text-indigo-300"
                    >
                        Tahapan dossier
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Satu alur dari hasil teknis hingga mandat balasan.
                    </p>
                </div>
                <div
                    class="rounded-full bg-indigo-600/10 px-3 py-1 text-sm font-bold text-indigo-700 dark:text-indigo-200"
                >
                    {{ dossier.progress.percent }}%
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div
                    v-for="(stage, index) in stages"
                    :key="stage.label"
                    class="relative flex gap-3"
                >
                    <div
                        v-if="index < stages.length - 1"
                        class="absolute top-8 left-4 hidden h-px w-[calc(100%-1rem)] bg-border md:block"
                        aria-hidden="true"
                    />
                    <div
                        class="relative z-10 grid size-8 shrink-0 place-items-center rounded-full border bg-background"
                        :class="
                            index < activeStage
                                ? 'border-emerald-500 bg-emerald-500 text-white'
                                : index === activeStage
                                  ? 'border-indigo-500 text-indigo-600'
                                  : 'text-muted-foreground'
                        "
                    >
                        <Check v-if="index < activeStage" class="size-4" />
                        <component :is="stage.icon" v-else class="size-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm leading-tight font-semibold">
                            {{ stage.label }}
                        </p>
                        <p class="mt-1 text-xs leading-4 text-muted-foreground">
                            {{ stage.caption }}
                        </p>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
