<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, CheckCircle2, Send } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import { toast } from 'vue-sonner';
import ManualDocumentReviewStep from '@/components/back-office/intake/manual/ManualDocumentReviewStep.vue';
import ManualIntakeProgress from '@/components/back-office/intake/manual/ManualIntakeProgress.vue';
import ManualIntakeSummaryRail from '@/components/back-office/intake/manual/ManualIntakeSummaryRail.vue';
import ManualLetterStep from '@/components/back-office/intake/manual/ManualLetterStep.vue';
import ManualSenderStep from '@/components/back-office/intake/manual/ManualSenderStep.vue';
import { Button } from '@/components/ui/button';
import { manualIntakeChecklist } from '@/lib/manualIntakePreview';
import type {
    ManualIntakeFormPayload,
    ManualIntakeInitialData,
    ManualIntakeRoutes,
    ManualIntakeStep,
} from '@/types';

const props = defineProps<{
    routes?: ManualIntakeRoutes;
    mode?: 'create' | 'revision';
    initial?: ManualIntakeInitialData;
    preview?: boolean;
}>();

const formRoot = ref<HTMLFormElement | null>(null);
const currentStep = ref<ManualIntakeStep>(1);
const previewSaved = ref(false);

function officeDateParts(): Record<string, string> {
    return Object.fromEntries(
        new Intl.DateTimeFormat('en-CA', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23',
            timeZone: 'Asia/Makassar',
        })
            .formatToParts(new Date())
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    );
}

const officeNow = officeDateParts();
const today = `${officeNow.year}-${officeNow.month}-${officeNow.day}`;
const defaultReceivedAt = `${today}T${officeNow.hour}:${officeNow.minute}`;

const form = useForm<ManualIntakeFormPayload>({
    sender_organization_name: props.initial?.sender_organization_name ?? '',
    contact_name: props.initial?.contact_name ?? '',
    contact_email: props.initial?.contact_email ?? '',
    contact_phone: props.initial?.contact_phone ?? '',
    received_at: props.initial?.received_at || defaultReceivedAt,
    external_letter_number: props.initial?.external_letter_number ?? '',
    external_letter_date: props.initial?.external_letter_date ?? '',
    subject: props.initial?.subject ?? '',
    summary: props.initial?.summary ?? '',
    document: null,
    checklist: (props.initial?.checklist ?? manualIntakeChecklist).map(
        (item) => ({ ...item }),
    ),
    screening_note: props.initial?.screening_note ?? '',
});

const progress = computed(() => form.progress?.percentage);

function focusFirstError(): void {
    void nextTick(() =>
        formRoot.value
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus(),
    );
}

function validateSenderStep(): boolean {
    form.clearErrors(
        'sender_organization_name',
        'contact_name',
        'contact_email',
        'received_at',
    );

    if (!form.sender_organization_name.trim()) {
        form.setError(
            'sender_organization_name',
            'Nama instansi atau organisasi wajib diisi.',
        );
    }

    if (!form.contact_name.trim()) {
        form.setError('contact_name', 'Nama kontak wajib diisi.');
    }

    if (
        form.contact_email &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.contact_email)
    ) {
        form.setError('contact_email', 'Masukkan alamat email yang valid.');
    }

    if (!form.received_at) {
        form.setError('received_at', 'Waktu penerimaan wajib diisi.');
    }

    return ![
        form.errors.sender_organization_name,
        form.errors.contact_name,
        form.errors.contact_email,
        form.errors.received_at,
    ].some(Boolean);
}

function validateLetterStep(): boolean {
    form.clearErrors('external_letter_date', 'subject');

    if (!form.subject.trim()) {
        form.setError('subject', 'Perihal surat wajib diisi.');
    }

    if (form.external_letter_date && form.external_letter_date > today) {
        form.setError(
            'external_letter_date',
            'Tanggal surat tidak boleh melewati hari ini.',
        );
    }

    return ![form.errors.external_letter_date, form.errors.subject].some(
        Boolean,
    );
}

function validateDocumentStep(): boolean {
    form.clearErrors('document', 'checklist');

    if (!form.document && !props.initial?.existing_document) {
        form.setError('document', 'Pilih hasil scan PDF sebelum menyimpan.');
    }

    if (!form.checklist.every((item) => item.checked)) {
        form.setError(
            'checklist',
            'Lengkapi seluruh konfirmasi pemeriksaan terlebih dahulu.',
        );
    }

    return ![form.errors.document, form.errors.checklist].some(Boolean);
}

function goNext(): void {
    const isValid =
        currentStep.value === 1 ? validateSenderStep() : validateLetterStep();

    if (!isValid) {
        focusFirstError();

        return;
    }

    currentStep.value = Math.min(3, currentStep.value + 1) as ManualIntakeStep;
    previewSaved.value = false;
    scrollToPageStart();
}

function goBack(): void {
    currentStep.value = Math.max(1, currentStep.value - 1) as ManualIntakeStep;
    scrollToPageStart();
}

function scrollToPageStart(): void {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    window.scrollTo({
        top: 0,
        behavior: reduceMotion ? 'auto' : 'smooth',
    });
}

function selectDocument(file?: File): void {
    form.clearErrors('document');

    if (!file) {
        form.document = null;

        return;
    }

    if (
        file.type !== 'application/pdf' &&
        !file.name.toLocaleLowerCase('id-ID').endsWith('.pdf')
    ) {
        form.document = null;
        form.setError('document', 'Pilih berkas dengan format PDF.');

        return;
    }

    if (file.size > 20 * 1024 * 1024) {
        form.document = null;
        form.setError('document', 'Ukuran PDF tidak boleh melebihi 20 MB.');

        return;
    }

    form.document = file;
    previewSaved.value = false;
}

function toggleChecklist(id: string, checked: boolean): void {
    form.checklist = form.checklist.map((item) =>
        item.id === id ? { ...item, checked } : item,
    );
    form.clearErrors('checklist');
    previewSaved.value = false;
}

function submit(): void {
    if (!validateSenderStep()) {
        currentStep.value = 1;
        focusFirstError();

        return;
    }

    if (!validateLetterStep()) {
        currentStep.value = 2;
        focusFirstError();

        return;
    }

    if (!validateDocumentStep()) {
        currentStep.value = 3;
        focusFirstError();

        return;
    }

    if (props.preview === true) {
        previewSaved.value = true;
        toast.success('Simulasi surat manual siap diajukan ke Kabag Umum.');

        return;
    }

    if (!props.routes?.store) {
        toast.error('Endpoint pencatatan surat manual belum tersedia.');

        return;
    }

    form.post(props.routes.store, {
        forceFormData: true,
        preserveScroll: true,
        onError: focusFirstError,
    });
}
</script>

<template>
    <ManualIntakeProgress
        :current-step="currentStep"
        @select="currentStep = $event"
    />

    <div
        v-if="previewSaved"
        class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100"
        role="status"
        aria-live="polite"
    >
        <CheckCircle2 class="mt-0.5 size-5 shrink-0" aria-hidden="true" />
        <div>
            <p class="text-sm font-semibold">Simulasi pencatatan berhasil</p>
            <p class="mt-1 text-xs leading-5 opacity-80">
                Pada produksi, data dan scan akan disimpan atomik lalu masuk
                antrean persetujuan Kabag Umum.
            </p>
        </div>
    </div>

    <div
        v-if="mode === 'revision' && initial?.return_note"
        class="rounded-2xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-950 dark:border-orange-900 dark:bg-orange-950/25 dark:text-orange-100"
        role="status"
    >
        <p class="font-semibold">Catatan perbaikan dari Kabag Umum</p>
        <p class="mt-1 leading-6 opacity-80">{{ initial.return_note }}</p>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start">
        <form
            ref="formRoot"
            class="overflow-hidden rounded-2xl border bg-card shadow-sm"
            novalidate
            @submit.prevent="submit"
        >
            <div
                v-if="Object.keys(form.errors).length > 1"
                class="border-b border-destructive/25 bg-destructive/6 px-5 py-4 text-sm text-destructive sm:px-6"
                role="alert"
            >
                Beberapa informasi perlu diperiksa kembali. Detailnya tersedia
                di bawah kolom terkait.
            </div>

            <div class="p-5 sm:p-6 lg:p-8">
                <ManualSenderStep
                    v-if="currentStep === 1"
                    v-model:organization="form.sender_organization_name"
                    v-model:contact-name="form.contact_name"
                    v-model:contact-email="form.contact_email"
                    v-model:contact-phone="form.contact_phone"
                    v-model:received-at="form.received_at"
                    :errors="form.errors"
                />

                <ManualLetterStep
                    v-else-if="currentStep === 2"
                    v-model:letter-number="form.external_letter_number"
                    v-model:letter-date="form.external_letter_date"
                    v-model:subject="form.subject"
                    v-model:summary="form.summary"
                    :today="today"
                    :errors="form.errors"
                />

                <ManualDocumentReviewStep
                    v-else
                    :document="form.document"
                    :checklist="form.checklist"
                    :note="form.screening_note"
                    :document-error="form.errors.document"
                    :checklist-error="form.errors.checklist"
                    :note-error="form.errors.screening_note"
                    :progress="progress"
                    :existing-document="initial?.existing_document"
                    @select-document="selectDocument"
                    @toggle-checklist="toggleChecklist"
                    @update:note="form.screening_note = $event"
                />
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t bg-muted/20 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <Button
                    v-if="currentStep === 1"
                    as-child
                    variant="ghost"
                    class="min-h-11 rounded-xl"
                >
                    <Link
                        :href="routes?.intake_index ?? '/back-office/dashboard'"
                    >
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        Batal
                    </Link>
                </Button>
                <Button
                    v-else
                    type="button"
                    variant="ghost"
                    class="min-h-11 cursor-pointer rounded-xl"
                    @click="goBack"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Kembali
                </Button>

                <Button
                    v-if="currentStep < 3"
                    type="button"
                    class="min-h-11 cursor-pointer rounded-xl bg-emerald-700 px-5 text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400"
                    @click="goNext"
                >
                    Lanjutkan
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Button>
                <Button
                    v-else
                    type="submit"
                    class="min-h-11 cursor-pointer rounded-xl bg-emerald-700 px-5 text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400"
                    :disabled="form.processing"
                >
                    <Send class="size-4" aria-hidden="true" />
                    {{
                        form.processing
                            ? 'Menyimpan…'
                            : mode === 'revision'
                              ? 'Simpan perbaikan & ajukan kembali'
                              : 'Simpan & ajukan ke Kabag Umum'
                    }}
                </Button>
            </div>
        </form>

        <ManualIntakeSummaryRail
            :form="form"
            :current-step="currentStep"
            :has-existing-document="Boolean(initial?.existing_document)"
        />
    </div>
</template>
