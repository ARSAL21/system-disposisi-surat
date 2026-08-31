<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue';
import { computed } from 'vue';
import LetterActivityChangePanel from '@/components/back-office/letter-activities/LetterActivityChangePanel.vue';
import LetterActivityContext from '@/components/back-office/letter-activities/LetterActivityContext.vue';
import LetterActivityTechnicalDetails from '@/components/back-office/letter-activities/LetterActivityTechnicalDetails.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import {
    letterActivityActionClass,
    letterActivityActionDescriptions,
    letterActivityActionLabels,
} from '@/lib/letterActivityPresentation';
import type { LetterActivityRecord, LetterActivityVisibility } from '@/types';

const props = defineProps<{
    open: boolean;
    activity: LetterActivityRecord | null;
    timezone: string;
    visibility: LetterActivityVisibility;
}>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent class="sm:max-w-3xl">
            <template v-if="activity">
                <DialogHeader>
                    <div class="mb-2 flex flex-wrap items-center gap-2 pr-8">
                        <span
                            class="flex size-11 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-700 dark:text-indigo-300"
                        >
                            <ShieldCheck class="size-5" aria-hidden="true" />
                        </span>
                        <Badge
                            variant="outline"
                            :class="letterActivityActionClass(activity.action)"
                        >
                            {{ letterActivityActionLabels[activity.action] }}
                        </Badge>
                    </div>
                    <DialogTitle
                        >Detail aktivitas #{{ activity.id }}</DialogTitle
                    >
                    <DialogDescription class="leading-6">
                        {{ letterActivityActionDescriptions[activity.action] }}
                        Catatan aktivitas ini hanya dapat dibaca dan tidak dapat
                        diubah dari halaman ini.
                    </DialogDescription>
                </DialogHeader>

                <LetterActivityContext
                    :activity="activity"
                    :timezone="timezone"
                    :visibility="visibility"
                />

                <template v-if="visibility === 'details'">
                    <div class="grid gap-3 lg:grid-cols-2">
                        <LetterActivityChangePanel
                            title="Sebelum"
                            :changes="activity.before"
                            tone="before"
                        />
                        <LetterActivityChangePanel
                            title="Sesudah"
                            :changes="activity.after"
                            tone="after"
                        />
                    </div>

                    <LetterActivityTechnicalDetails :activity="activity" />
                </template>

                <section
                    v-else
                    class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200"
                >
                    Detail surat, perubahan, dokumen, dan jejak teknis telah
                    disembunyikan sesuai batas akses administrator teknis.
                </section>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11"
                        >
                            Tutup
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </template>
            <div v-else class="space-y-4" aria-label="Memuat detail aktivitas">
                <Skeleton class="h-7 w-48 motion-reduce:animate-none" />
                <Skeleton class="h-24 w-full motion-reduce:animate-none" />
                <Skeleton class="h-40 w-full motion-reduce:animate-none" />
            </div>
        </DialogScrollContent>
    </Dialog>
</template>
