<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, FileWarning } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import DispositionInstructionCard from '@/components/back-office/dispositions/DispositionInstructionCard.vue';
import ForwardDispositionPanel from '@/components/back-office/dispositions/ForwardDispositionPanel.vue';
import ForwardedDispositionReceiptCard from '@/components/back-office/dispositions/ForwardedDispositionReceiptCard.vue';
import RoutingDetailHeader from '@/components/back-office/routing/RoutingDetailHeader.vue';
import RoutingLetterOverviewCard from '@/components/back-office/routing/RoutingLetterOverviewCard.vue';
import RoutingOfficialDocumentCard from '@/components/back-office/routing/RoutingOfficialDocumentCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    previewDispositionInboxItems,
    previewSectionHeadPositions,
} from '@/lib/dispositionPreview';
import type {
    DispositionInboxDetailRoutes,
    DispositionInboxItem,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    ForwardDispositionCapabilities,
    ForwardDispositionPayload,
    ForwardDispositionReceipt,
} from '@/types';

const props = defineProps<{
    disposition?: DispositionInboxItem;
    sectionHeadPositions?: DispositionPositionOption[];
    instructionLabels?: DispositionInstructionLabelOption[];
    forwardedDisposition?: ForwardDispositionReceipt | null;
    capabilities?: ForwardDispositionCapabilities;
    routes?: DispositionInboxDetailRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Inbox Disposisi',
                href: '/back-office/dispositions/inbox',
            },
            { title: 'Detail Disposisi', href: '#' },
        ],
    },
});

const page = usePage();
const previewMode = computed(() => props.preview === true);
const previewRecipientId = computed(() => {
    const match = page.url
        .split('?')[0]
        .match(/\/dispositions\/inbox\/recipients\/(\d+)$/);

    return match ? Number(match[1]) : null;
});
const activeDisposition = computed(() =>
    previewMode.value
        ? (previewDispositionInboxItems.find(
              (disposition) =>
                  disposition.recipient_id === previewRecipientId.value,
          ) ?? previewDispositionInboxItems[0])
        : (props.disposition ?? null),
);
const sectionHeadPositions = computed(() =>
    previewMode.value
        ? previewSectionHeadPositions
        : (props.sectionHeadPositions ?? []),
);
const instructionLabels = computed(() =>
    previewMode.value
        ? [
              {
                  id: 31,
                  code: 'FOLLOW_UP',
                  name: 'Tindak lanjuti',
                  description: 'Memerlukan tindak lanjut sesuai kewenangan.',
              },
              {
                  id: 32,
                  code: 'COORDINATE',
                  name: 'Koordinasikan',
                  description: 'Memerlukan koordinasi dengan unit terkait.',
              },
              {
                  id: 33,
                  code: 'REVIEW',
                  name: 'Pelajari dan telaah',
                  description: 'Memerlukan kajian atau telaah lebih lanjut.',
              },
              {
                  id: 34,
                  code: 'URGENT',
                  name: 'Segera',
                  description: 'Memerlukan penanganan dengan prioritas tinggi.',
              },
          ]
        : (props.instructionLabels ?? []),
);
const indexUrl = computed(
    () =>
        (previewMode.value
            ? '/back-office/previews/dispositions/inbox'
            : props.routes?.index) ?? '/back-office/dispositions/inbox',
);
const interfaceNotice = ref('');
const successNotice = ref('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const simulatedForwarding = ref<ForwardDispositionReceipt | null>(null);
const activeForwarding = computed(
    () => simulatedForwarding.value ?? props.forwardedDisposition ?? null,
);
const canForward = computed(
    () =>
        !activeForwarding.value &&
        (previewMode.value ||
            props.capabilities?.can_forward_disposition === true),
);
let previewTimer: ReturnType<typeof setTimeout> | null = null;

function handleDocumentAction(action: 'preview' | 'download'): void {
    interfaceNotice.value =
        action === 'preview'
            ? 'Pratinjau lokal tidak membuka PDF privat. Endpoint produksi akan memverifikasi penerima disposisi dan Position Assignment aktif.'
            : 'Unduhan fixture dinonaktifkan. Berkas produksi hanya dilayani melalui endpoint privat yang terotorisasi.';
}

function forwardDisposition(payload: ForwardDispositionPayload): void {
    errors.value = {};
    successNotice.value = '';

    if (previewMode.value) {
        const recipients = sectionHeadPositions.value.filter((position) =>
            payload.recipient_position_ids.includes(position.id),
        );
        const instructions = instructionLabels.value.filter((label) =>
            payload.instruction_label_ids.includes(label.id),
        );

        if (recipients.length === 0) {
            errors.value = {
                recipient_position_ids:
                    'Pilih sedikitnya satu Kepala Bagian penerima.',
            };

            return;
        }

        if (instructions.length === 0) {
            errors.value = {
                instruction_label_ids:
                    'Pilih sedikitnya satu instruksi disposisi.',
            };

            return;
        }

        processing.value = true;
        previewTimer = setTimeout(() => {
            simulatedForwarding.value = {
                instructions: instructions.map((instruction) => ({
                    code: instruction.code,
                    name: instruction.name,
                })),
                instruction_note: payload.instruction_note || null,
                recipients: recipients.map((recipient) => ({
                    recipient_position: recipient,
                    status: 'PENDING',
                    received_at: '2026-09-01T10:15:00+08:00',
                })),
                disposed_by: {
                    name: 'Drs. Abdul Malik, M.Si.',
                    position: 'Asisten Pemerintahan dan Kesejahteraan Rakyat',
                    unit: 'Sekretariat Daerah',
                },
                disposed_at: '2026-09-01T10:15:00+08:00',
            };
            successNotice.value =
                'Simulasi penerusan selesai. Tidak ada data backend atau audit yang dibuat.';
            processing.value = false;
            previewTimer = null;
        }, 650);

        return;
    }

    if (!props.routes?.store) {
        errors.value = {
            recipient_position_ids:
                'Endpoint penerusan belum tersedia. Muat ulang halaman setelah backend M6.2 diaktifkan.',
        };

        return;
    }

    router.post(props.routes.store, payload, {
        preserveScroll: true,
        onStart: () => {
            processing.value = true;
        },
        onError: (responseErrors) => {
            errors.value = responseErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}

onBeforeUnmount(() => {
    if (previewTimer) {
        clearTimeout(previewTimer);
    }
});
</script>

<template>
    <Head
        :title="
            activeDisposition
                ? `Disposisi ${activeDisposition.letter.agenda_number}`
                : 'Detail Disposisi'
        "
    />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <template v-if="activeDisposition">
            <RoutingDetailHeader
                :letter="activeDisposition.letter"
                :back-href="indexUrl"
                back-label="Kembali ke inbox disposisi"
                :preview="previewMode"
            />

            <Alert v-if="interfaceNotice">
                <FileWarning class="size-4" aria-hidden="true" />
                <AlertTitle>Fixture UI tanpa akses berkas privat</AlertTitle>
                <AlertDescription>{{ interfaceNotice }}</AlertDescription>
            </Alert>

            <Alert
                v-if="successNotice"
                class="border-emerald-300 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/25"
            >
                <CircleCheck
                    class="size-4 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <AlertTitle
                    >Penerusan ditampilkan pada mode simulasi</AlertTitle
                >
                <AlertDescription>{{ successNotice }}</AlertDescription>
            </Alert>

            <div
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.85fr)]"
            >
                <section class="grid gap-5" aria-label="Informasi surat">
                    <RoutingLetterOverviewCard
                        :letter="activeDisposition.letter"
                    />
                    <RoutingOfficialDocumentCard
                        :document="activeDisposition.current_document"
                        :preview="previewMode"
                        @preview="handleDocumentAction('preview')"
                        @download="handleDocumentAction('download')"
                    />
                </section>

                <aside aria-label="Instruksi disposisi">
                    <DispositionInstructionCard
                        :disposition="activeDisposition"
                    />
                </aside>
            </div>

            <section
                v-if="activeForwarding || canForward"
                class="mt-1"
                aria-label="Penerusan disposisi"
            >
                <ForwardedDispositionReceiptCard
                    v-if="activeForwarding"
                    :disposition="activeForwarding"
                />
                <ForwardDispositionPanel
                    v-else
                    :positions="sectionHeadPositions"
                    :instruction-labels="instructionLabels"
                    :can-forward="canForward"
                    :processing="processing"
                    :errors="errors"
                    @confirm="forwardDisposition"
                />
            </section>
        </template>

        <Alert v-else variant="destructive">
            <FileWarning class="size-4" aria-hidden="true" />
            <AlertTitle>Disposisi tidak tersedia</AlertTitle>
            <AlertDescription>
                Payload produksi tidak memuat disposisi. Fixture tidak
                diaktifkan karena halaman ini bukan mode pratinjau.
            </AlertDescription>
        </Alert>
    </main>
</template>
