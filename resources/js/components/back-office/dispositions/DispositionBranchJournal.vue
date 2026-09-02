<script setup lang="ts">
import { History, MessageSquareText, Send, ShieldCheck } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type {
    AddDispositionFollowUpPayload,
    DispositionBranchLifecycle,
} from '@/types';

const minimumNoteLength = 10;
const maximumNoteLength = 2000;

const props = defineProps<{
    branch: DispositionBranchLifecycle;
    canAdd: boolean;
    processing?: boolean;
    error?: string;
}>();

const emit = defineEmits<{
    add: [payload: AddDispositionFollowUpPayload];
}>();

const note = ref('');
const localError = ref('');
const noteLength = computed(() => note.value.length);
const mergedError = computed(() => props.error || localError.value);

watch(
    () => props.branch.follow_ups.length,
    (nextLength, previousLength) => {
        if (nextLength > previousLength) {
            note.value = '';
            localError.value = '';
        }
    },
);

function addFollowUp(): void {
    const normalizedNote = note.value.trim();

    if (normalizedNote.length < minimumNoteLength) {
        localError.value = `Catatan minimal ${minimumNoteLength} karakter.`;

        return;
    }

    if (normalizedNote.length > maximumNoteLength) {
        localError.value = 'Catatan maksimal 2.000 karakter.';

        return;
    }

    localError.value = '';
    emit('add', { note: normalizedNote });
}
</script>

<template>
    <section
        class="overflow-hidden rounded-3xl border bg-background/85 shadow-xs"
        aria-labelledby="branch-journal-title"
    >
        <div
            class="flex flex-col gap-3 border-b bg-muted/25 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <div class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-violet-500/10 text-violet-700 dark:text-violet-300"
                >
                    <History class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <h2 id="branch-journal-title" class="font-semibold">
                        Jurnal tindak lanjut
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Catatan kronologis yang tidak dapat diubah.
                    </p>
                </div>
            </div>
            <span
                class="w-fit rounded-full border bg-background px-3 py-1 text-xs font-semibold tabular-nums"
            >
                {{ branch.follow_ups.length }} catatan
            </span>
        </div>

        <div class="p-5 sm:p-6">
            <ol
                v-if="branch.follow_ups.length > 0"
                class="relative space-y-5 before:absolute before:top-2 before:bottom-2 before:left-[0.6875rem] before:w-px before:bg-border"
            >
                <li
                    v-for="(followUp, index) in branch.follow_ups"
                    :key="`${followUp.created_at}-${index}`"
                    class="relative grid grid-cols-[1.375rem_minmax(0,1fr)] gap-3"
                >
                    <span
                        class="relative z-10 mt-1.5 size-[1.375rem] rounded-full border-4 border-background bg-violet-600"
                        aria-hidden="true"
                    />
                    <article class="rounded-2xl border bg-muted/20 p-4">
                        <p
                            class="text-sm leading-6 whitespace-pre-wrap text-foreground"
                        >
                            {{ followUp.note }}
                        </p>
                        <div
                            class="mt-4 flex flex-col gap-1 border-t pt-3 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
                        >
                            <span>
                                <strong class="font-semibold text-foreground">
                                    {{ followUp.created_by.name }}
                                </strong>
                                · {{ followUp.created_by.position }}
                            </span>
                            <time
                                :datetime="followUp.created_at"
                                class="font-medium tabular-nums"
                            >
                                {{ formatRoutingDateTime(followUp.created_at) }}
                            </time>
                        </div>
                    </article>
                </li>
            </ol>

            <div
                v-else
                class="flex min-h-36 flex-col items-center justify-center rounded-2xl border border-dashed bg-muted/15 px-5 py-8 text-center"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
                >
                    <MessageSquareText class="size-5" aria-hidden="true" />
                </span>
                <p class="mt-3 font-medium">Belum ada catatan tindak lanjut</p>
                <p
                    class="mt-1 max-w-md text-sm leading-6 text-muted-foreground"
                >
                    Jurnal akan membentuk histori kerja setelah penanganan
                    dimulai.
                </p>
            </div>

            <form
                v-if="branch.status === 'IN_PROGRESS' && canAdd"
                class="mt-6 rounded-2xl border border-blue-200 bg-blue-50/55 p-4 sm:p-5 dark:border-blue-900 dark:bg-blue-950/20"
                @submit.prevent="addFollowUp"
            >
                <label for="branch-follow-up-note" class="font-semibold">
                    Tambahkan perkembangan
                    <span class="text-destructive" aria-hidden="true">*</span>
                </label>
                <p
                    id="branch-follow-up-help"
                    class="mt-1 text-sm leading-6 text-muted-foreground"
                >
                    Tulis hasil koordinasi, progres, atau kendala penting tanpa
                    mengubah catatan sebelumnya.
                </p>
                <textarea
                    id="branch-follow-up-note"
                    v-model="note"
                    rows="5"
                    class="mt-4 flex min-h-32 w-full resize-y rounded-xl border border-input bg-background px-3 py-3 text-base leading-6 shadow-xs ring-offset-background outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                    placeholder="Contoh: Koordinasi awal telah dilakukan dan data pendukung sedang diverifikasi..."
                    :maxlength="maximumNoteLength"
                    :disabled="processing"
                    :aria-invalid="Boolean(mergedError)"
                    aria-describedby="branch-follow-up-help branch-follow-up-counter branch-follow-up-error"
                    @input="localError = ''"
                />
                <div
                    class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div id="branch-follow-up-error" aria-live="polite">
                        <InputError :message="mergedError" />
                    </div>
                    <p
                        id="branch-follow-up-counter"
                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                    >
                        {{ noteLength.toLocaleString('id-ID') }}/2.000
                    </p>
                </div>
                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <p
                        class="flex max-w-md items-start gap-2 text-xs leading-5 text-muted-foreground"
                    >
                        <ShieldCheck
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Identitas dan jabatan pencatat ditentukan server saat
                        catatan disimpan.
                    </p>
                    <Button
                        type="submit"
                        class="min-h-11 bg-blue-700 px-5 hover:bg-blue-800"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        <Send v-else class="size-4" aria-hidden="true" />
                        {{
                            processing ? 'Menyimpan...' : 'Simpan perkembangan'
                        }}
                    </Button>
                </div>
            </form>
        </div>
    </section>
</template>
