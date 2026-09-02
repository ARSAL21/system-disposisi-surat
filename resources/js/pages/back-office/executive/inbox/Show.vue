<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, FileWarning } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import ExecutiveBranchProgressCard from '@/components/back-office/dispositions/ExecutiveBranchProgressCard.vue';
import FirstDispositionPanel from '@/components/back-office/dispositions/FirstDispositionPanel.vue';
import FirstDispositionReceiptCard from '@/components/back-office/dispositions/FirstDispositionReceiptCard.vue';
import InitialRouteReceiptCard from '@/components/back-office/routing/InitialRouteReceiptCard.vue';
import RoutingDetailHeader from '@/components/back-office/routing/RoutingDetailHeader.vue';
import RoutingLetterOverviewCard from '@/components/back-office/routing/RoutingLetterOverviewCard.vue';
import RoutingOfficialDocumentCard from '@/components/back-office/routing/RoutingOfficialDocumentCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    previewAssistantPositions,
    previewDispositionInstructionLabels,
    previewFirstDispositionReceipt,
} from '@/lib/dispositionPreview';
import {
    previewExecutiveBranchProgress,
    previewExecutiveInboxItems,
} from '@/lib/letterRoutingPreview';
import type {
    CreateFirstDispositionPayload,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    ExecutiveInboxItem,
    ExecutiveBranchProgress,
    FirstDispositionCapabilities,
    FirstDispositionReceipt,
    FirstDispositionRoutes,
} from '@/types';

const props = defineProps<{
    route?: ExecutiveInboxItem;
    assistantPositions?: DispositionPositionOption[];
    instructionLabels?: DispositionInstructionLabelOption[];
    firstDisposition?: FirstDispositionReceipt | null;
    branchProgress?: ExecutiveBranchProgress | null;
    capabilities?: FirstDispositionCapabilities;
    routes?: FirstDispositionRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Inbox Pimpinan', href: '/back-office/executive/inbox' },
            { title: 'Detail Naskah Dinas', href: '#' },
        ],
    },
});

const page = usePage();
const previewMode = computed(() => props.preview === true);
const previewRouteId = computed(() => {
    const match = page.url
        .split('?')[0]
        .match(/\/executive-inbox\/routes\/(\d+)$/);

    return match ? Number(match[1]) : null;
});
const baseRoute = computed(() =>
    previewMode.value
        ? (previewExecutiveInboxItems.find(
              (route) => route.route_id === previewRouteId.value,
          ) ?? previewExecutiveInboxItems[0])
        : (props.route ?? null),
);
const simulatedDisposition = ref<FirstDispositionReceipt | null>(null);
const activeDisposition = computed(() => {
    if (simulatedDisposition.value) {
        return simulatedDisposition.value;
    }

    if (previewMode.value && previewRouteId.value === 504) {
        return previewFirstDispositionReceipt;
    }

    return props.firstDisposition ?? null;
});
const activeBranchProgress = computed(() =>
    previewMode.value && previewRouteId.value === 504
        ? previewExecutiveBranchProgress
        : (props.branchProgress ?? null),
);
const activeRoute = computed<ExecutiveInboxItem | null>(() => {
    if (!baseRoute.value || !activeDisposition.value) {
        return baseRoute.value;
    }

    return {
        ...baseRoute.value,
        letter: {
            ...baseRoute.value.letter,
            current_route: baseRoute.value.letter.current_route
                ? {
                      ...baseRoute.value.letter.current_route,
                      status: 'COMPLETED',
                  }
                : null,
        },
    };
});
const assistantPositions = computed(() =>
    previewMode.value
        ? previewAssistantPositions
        : (props.assistantPositions ?? []),
);
const instructionLabels = computed(() =>
    previewMode.value
        ? previewDispositionInstructionLabels
        : (props.instructionLabels ?? []),
);
const canCreateDisposition = computed(
    () =>
        !activeDisposition.value &&
        (previewMode.value ||
            props.capabilities?.can_create_disposition === true),
);
const indexUrl = computed(
    () =>
        (previewMode.value
            ? '/back-office/previews/executive-inbox'
            : props.routes?.index) ?? '/back-office/executive/inbox',
);
const interfaceNotice = ref('');
const successNotice = ref('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});
let previewTimer: ReturnType<typeof setTimeout> | null = null;

function handleDocumentAction(action: 'preview' | 'download'): void {
    interfaceNotice.value =
        action === 'preview'
            ? 'Pratinjau UI tidak membuka PDF privat. Versi produksi tetap menggunakan endpoint inbox yang terotorisasi.'
            : 'Unduhan fixture dinonaktifkan. Versi produksi hanya akan mengunduh melalui endpoint privat yang terotorisasi.';
}

function createDisposition(payload: CreateFirstDispositionPayload): void {
    errors.value = {};
    successNotice.value = '';

    if (previewMode.value) {
        const recipients = assistantPositions.value.filter(
            (position) =>
                payload.recipient_position_ids.includes(position.id) &&
                position.level_code === 'ASSISTANT' &&
                position.is_available,
        );
        const instructions = instructionLabels.value.filter((label) =>
            payload.instruction_label_ids.includes(label.id),
        );

        if (
            recipients.length === 0 ||
            recipients.length !== payload.recipient_position_ids.length ||
            recipients.length > 3
        ) {
            errors.value = {
                recipient_position_ids:
                    'Pilih 1-3 jabatan Asisten yang tersedia tanpa duplikasi.',
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
            simulatedDisposition.value = {
                recipients: recipients.map((recipient) => ({
                    status: 'PENDING',
                    recipient_position: recipient,
                })),
                instructions: instructions.map((instruction) => ({
                    code: instruction.code,
                    name: instruction.name,
                })),
                instruction_note: payload.instruction_note || null,
                disposed_by: {
                    name: 'Dr. H. Ahmad Darmawan, S.E., M.Si.',
                    position: 'Wali Kota',
                    unit: 'Pemerintah Kota',
                },
                disposed_at: '2026-08-31T11:08:00+08:00',
            };
            successNotice.value =
                'Simulasi disposisi selesai. Tidak ada data backend atau audit yang dibuat.';
            processing.value = false;
            previewTimer = null;
        }, 650);

        return;
    }

    if (!props.routes?.store) {
        errors.value = {
            recipient_position_ids:
                'Endpoint disposisi belum tersedia. Muat ulang halaman setelah backend M6 diaktifkan.',
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
            activeRoute
                ? `Inbox ${activeRoute.letter.agenda_number}`
                : 'Detail Inbox Pimpinan'
        "
    />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <template v-if="activeRoute">
            <!-- 1. Executive Detail Header -->
            <RoutingDetailHeader
                :letter="activeRoute.letter"
                :back-href="indexUrl"
                back-label="Kembali ke Inbox Pimpinan"
                :preview="previewMode"
            />

            <!-- Alerts -->
            <Alert v-if="interfaceNotice" class="rounded-2xl">
                <FileWarning class="size-4" />
                <AlertTitle>Pratinjau Lokal Berkas</AlertTitle>
                <AlertDescription class="text-xs">{{ interfaceNotice }}</AlertDescription>
            </Alert>

            <Alert
                v-if="successNotice"
                class="rounded-2xl border-emerald-500/30 bg-emerald-50/70 dark:border-emerald-500/20 dark:bg-emerald-950/30"
            >
                <CircleCheck class="size-4 text-emerald-600 dark:text-emerald-400" />
                <AlertTitle class="text-xs font-bold text-emerald-800 dark:text-emerald-200">
                    Disposisi Ditampilkan Pada Mode Simulasi
                </AlertTitle>
                <AlertDescription class="text-xs text-emerald-700 dark:text-emerald-300">
                    {{ successNotice }}
                </AlertDescription>
            </Alert>

            <!-- 2. Main 2-Column Responsive Dossier & Disposition Layout -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- Left Column (7 Cols): Dossier & Official Document Card -->
                <div class="space-y-6 lg:col-span-7">
                    <!-- Letter Dossier Overview Card -->
                    <RoutingLetterOverviewCard :letter="activeRoute.letter" />

                    <!-- Official Document & SHA-256 Card -->
                    <RoutingOfficialDocumentCard
                        :document="activeRoute.letter.current_document"
                        :preview="previewMode"
                        @preview="handleDocumentAction('preview')"
                        @download="handleDocumentAction('download')"
                    />
                </div>

                <!-- Right Column (5 Cols): Disposition Desk & Provenance Cards -->
                <div class="space-y-6 lg:col-span-5">
                    <!-- 1. Disposition Panel (Active form if not yet disposed) -->
                    <FirstDispositionPanel
                        v-if="!activeDisposition"
                        :positions="assistantPositions"
                        :instruction-labels="instructionLabels"
                        :can-create="canCreateDisposition"
                        :processing="processing"
                        :errors="errors"
                        @confirm="createDisposition"
                    />

                    <!-- 2. First Disposition Receipt (If already created) -->
                    <FirstDispositionReceiptCard
                        v-if="activeDisposition"
                        :disposition="activeDisposition"
                    />

                    <!-- 3. Branch Progress Monitoring (If in progress/completed) -->
                    <ExecutiveBranchProgressCard
                        v-if="activeDisposition && activeBranchProgress"
                        :progress="activeBranchProgress"
                    />

                    <!-- 4. Initial Route Provenance Receipt -->
                    <InitialRouteReceiptCard
                        v-if="activeRoute.letter.current_route"
                        :route="activeRoute.letter.current_route"
                    />
                </div>
            </div>
        </template>

        <Alert v-else variant="destructive" class="rounded-2xl">
            <FileWarning class="size-4" />
            <AlertTitle>Route Surat Tidak Ditemukan</AlertTitle>
            <AlertDescription class="text-xs">
                Data naskah dinas tidak ditemukan atau Anda tidak memiliki hak akses untuk memeriksa rincian disposisi ini.
            </AlertDescription>
        </Alert>
    </main>
</template>
