<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    Check,
    Clock3,
    FileSearch,
    Send,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type {
    ExpertAdvisorOption,
    ExpertConsultation,
    RequestExpertConsultationPayload,
} from '@/types';

const props = defineProps<{
    advisors: ExpertAdvisorOption[];
    consultations: ExpertConsultation[];
    canRequest: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    request: [payload: RequestExpertConsultationPayload];
}>();

const selectedIds = ref<number[]>([]);
const note = ref('');

const pendingConsultations = computed(() =>
    props.consultations.filter((consultation) => consultation.status === 'PENDING'),
);
const reportedConsultations = computed(() =>
    props.consultations.filter((consultation) => consultation.status === 'REPORTED'),
);
const availableAdvisors = computed(() =>
    props.advisors.filter((advisor) => {
        const alreadyRequested = props.consultations.some(
            (consultation) => consultation.advisor.id === advisor.id,
        );

        return advisor.is_available && !alreadyRequested;
    }),
);
const localError = computed(() => {
    if (selectedIds.value.length > 3) {
        return 'Pilih maksimal tiga Staf Ahli dalam satu permintaan.';
    }

    if (selectedIds.value.length === 0) {
        return 'Pilih sedikitnya satu Staf Ahli.';
    }

    if (note.value.trim().length > 2000) {
        return 'Catatan arahan maksimal 2.000 karakter.';
    }

    return '';
});

function toggleAdvisor(id: number, checked: boolean): void {
    selectedIds.value = checked
        ? [...new Set([...selectedIds.value, id])]
        : selectedIds.value.filter((selectedId) => selectedId !== id);
}

function submit(): void {
    if (!props.canRequest || props.processing || localError.value) {
        return;
    }

    emit('request', {
        expert_position_ids: selectedIds.value,
        request_note: note.value.trim(),
    });
}
</script>

<template>
    <section
        class="overflow-hidden rounded-3xl border border-amber-200/80 bg-gradient-to-br from-amber-50/80 via-background to-orange-50/60 shadow-sm dark:border-amber-900/60 dark:from-amber-950/35 dark:via-background dark:to-orange-950/20"
        aria-labelledby="expert-consultation-title"
    >
        <div class="border-b border-amber-200/70 p-5 dark:border-amber-900/50 sm:p-6">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300"
                >
                    <FileSearch class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="expert-consultation-title" class="font-semibold">
                            Minta telaah Staf Ahli
                        </h2>
                        <Badge
                            variant="outline"
                            class="border-amber-300/80 bg-amber-100/60 text-[10px] text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200"
                        >
                            Jalur konsultasi
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Minta pertimbangan bidang tertentu sebelum Anda menentukan
                        arahan surat kepada Sekda.
                    </p>
                </div>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-3">
                <div class="flex items-center gap-2 rounded-2xl bg-background/75 p-3 text-xs">
                    <Clock3 class="size-4 text-amber-600 dark:text-amber-300" />
                    <span><strong>{{ pendingConsultations.length }}</strong> menunggu telaah</span>
                </div>
                <div class="flex items-center gap-2 rounded-2xl bg-background/75 p-3 text-xs">
                    <Check class="size-4 text-emerald-600 dark:text-emerald-300" />
                    <span><strong>{{ reportedConsultations.length }}</strong> sudah dilaporkan</span>
                </div>
                <div class="flex items-center gap-2 rounded-2xl bg-background/75 p-3 text-xs">
                    <ShieldCheck class="size-4 text-slate-600 dark:text-slate-300" />
                    <span>Hasil kembali ke Wali Kota</span>
                </div>
            </div>
        </div>

        <div class="space-y-4 p-5 sm:p-6">
            <Alert v-if="!canRequest" class="border-amber-200/80 bg-background/70 dark:border-amber-900/60">
                <ShieldCheck class="size-4" aria-hidden="true" />
                <AlertTitle>Akses baca-saja</AlertTitle>
                <AlertDescription>
                    Hanya Wali Kota yang dapat meminta telaah pada tahap ini.
                </AlertDescription>
            </Alert>

            <div
                v-if="consultations.length > 0"
                class="space-y-2"
                aria-label="Status telaah yang sudah diminta"
            >
                <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Telaah yang sudah diminta
                </p>
                <div
                    v-for="consultation in consultations"
                    :key="consultation.id"
                    class="flex items-center gap-3 rounded-2xl border border-border/70 bg-background/75 p-3"
                >
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300">
                        <UserRound class="size-4" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ consultation.advisor.name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ consultation.advisor.holder_name || 'Jabatan belum memiliki pemegang' }}</p>
                    </div>
                    <Badge
                        :class="consultation.status === 'REPORTED'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                            : consultation.status === 'CANCELLED'
                              ? 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'
                              : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300'"
                        variant="outline"
                    >
                        {{ consultation.status === 'REPORTED' ? 'Sudah dilaporkan' : consultation.status === 'CANCELLED' ? 'Dibatalkan' : 'Menunggu telaah' }}
                    </Badge>
                    <Link
                        v-if="consultation.status === 'REPORTED' && consultation.links.show"
                        :href="consultation.links.show"
                        class="text-xs font-semibold text-amber-700 hover:underline dark:text-amber-300"
                    >
                        Lihat
                    </Link>
                </div>
            </div>

            <div v-if="canRequest && availableAdvisors.length > 0" class="space-y-3">
                <div>
                    <Label>Pilih bidang yang ingin dimintai pertimbangan</Label>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        Anda dapat memilih beberapa bidang. Setiap hasil akan kembali ke meja Wali Kota.
                    </p>
                </div>
                <label
                    v-for="advisor in availableAdvisors"
                    :key="advisor.id"
                    class="flex cursor-pointer items-start gap-3 rounded-2xl border border-border/80 bg-background p-3 transition-colors hover:border-amber-300 hover:bg-amber-50/45 dark:hover:border-amber-800 dark:hover:bg-amber-950/20"
                >
                    <Checkbox
                        :model-value="selectedIds.includes(advisor.id)"
                        @update:model-value="toggleAdvisor(advisor.id, $event === true)"
                    />
                    <span class="min-w-0">
                        <span class="block text-sm font-medium">{{ advisor.name }}</span>
                        <span class="mt-1 block text-xs leading-5 text-muted-foreground">{{ advisor.field }}</span>
                        <span class="mt-1 flex items-center gap-1 text-xs text-foreground/75">
                            <UserRound class="size-3" aria-hidden="true" />
                            {{ advisor.holder_name || 'Belum ada pemegang jabatan' }}
                        </span>
                    </span>
                </label>
                <InputError :message="errors?.expert_position_ids || localError" />

                <div class="space-y-2">
                    <Label for="expert-consultation-note">
                        Catatan untuk Staf Ahli <span class="text-muted-foreground">(opsional)</span>
                    </Label>
                    <Textarea
                        id="expert-consultation-note"
                        v-model="note"
                        :disabled="processing"
                        maxlength="2000"
                        placeholder="Jelaskan bagian yang perlu dipelajari atau pertimbangkan."
                    />
                    <InputError :message="errors?.request_note" />
                </div>

                <Button
                    type="button"
                    class="min-h-11 w-full bg-amber-600 text-white hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-400"
                    :disabled="processing || Boolean(localError)"
                    @click="submit"
                >
                    <Send v-if="!processing" class="size-4" aria-hidden="true" />
                    <BookOpenCheck v-else class="size-4 animate-pulse" aria-hidden="true" />
                    {{ processing ? 'Mengirim permintaan...' : 'Kirim permintaan telaah' }}
                </Button>
            </div>

            <Alert v-else-if="consultations.some((item) => item.status === 'PENDING')" class="border-amber-200/80 bg-background/70 dark:border-amber-900/60">
                <Clock3 class="size-4" aria-hidden="true" />
                <AlertTitle>Menunggu laporan telaah</AlertTitle>
                <AlertDescription>
                    Setelah seluruh telaah selesai, Anda dapat meneruskan arahan formal kepada Sekda.
                </AlertDescription>
            </Alert>

            <Alert v-else class="border-emerald-200/80 bg-emerald-50/50 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                <Check class="size-4 text-emerald-600 dark:text-emerald-300" aria-hidden="true" />
                <AlertTitle>Tidak ada permintaan telaah aktif</AlertTitle>
                <AlertDescription>
                    Anda dapat langsung meneruskan arahan formal kepada Sekda atau meminta telaah tambahan.
                </AlertDescription>
            </Alert>
        </div>
    </section>
</template>
