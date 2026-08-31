<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, FileWarning } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
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
} from '@/lib/dispositionPreview';
import { previewExecutiveInboxItems } from '@/lib/letterRoutingPreview';
import type {
    CreateFirstDispositionPayload,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    ExecutiveInboxItem,
    FirstDispositionCapabilities,
    FirstDispositionReceipt,
    FirstDispositionRoutes,
} from '@/types';

const props = defineProps<{
    route?: ExecutiveInboxItem;
    assistantPositions?: DispositionPositionOption[];
    instructionLabels?: DispositionInstructionLabelOption[];
    firstDisposition?: FirstDispositionReceipt | null;
    capabilities?: FirstDispositionCapabilities;
    routes?: FirstDispositionRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Inbox Pimpinan', href: '/back-office/executive/inbox' },
            { title: 'Detail Surat', href: '#' },
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
const activeDisposition = computed(
    () => simulatedDisposition.value ?? props.firstDisposition ?? null,
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
        const recipient = assistantPositions.value.find(
            (position) =>
                position.id === payload.recipient_position_id &&
                position.level_code === 'ASSISTANT' &&
                position.is_available,
        );
        const instructions = instructionLabels.value.filter((label) =>
            payload.instruction_label_ids.includes(label.id),
        );

        if (!recipient) {
            errors.value = {
                recipient_position_id:
                    'Pilih satu jabatan Asisten yang tersedia.',
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
                status: 'PENDING',
                recipient_position: recipient,
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
            recipient_position_id:
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

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <template v-if="activeRoute">
            <RoutingDetailHeader
                :letter="activeRoute.letter"
                :back-href="indexUrl"
                back-label="Kembali ke inbox pimpinan"
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
                <AlertTitle>Disposisi ditampilkan pada mode simulasi</AlertTitle>
                <AlertDescription>{{ successNotice }}</AlertDescription>
            </Alert>

            <div
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.85fr)]"
            >
                <section class="grid gap-5" aria-label="Informasi surat">
                    <RoutingLetterOverviewCard :letter="activeRoute.letter" />
                    <RoutingOfficialDocumentCard
                        :document="activeRoute.letter.current_document"
                        :preview="previewMode"
                        @preview="handleDocumentAction('preview')"
                        @download="handleDocumentAction('download')"
                    />
                </section>

                <aside class="grid gap-5" aria-label="Keputusan disposisi">
                    <InitialRouteReceiptCard
                        v-if="activeRoute.letter.current_route"
                        :route="activeRoute.letter.current_route"
                    />
                    <FirstDispositionReceiptCard
                        v-if="activeDisposition"
                        :disposition="activeDisposition"
                    />
                    <FirstDispositionPanel
                        v-else
                        :positions="assistantPositions"
                        :instruction-labels="instructionLabels"
                        :can-create="canCreateDisposition"
                        :processing="processing"
                        :errors="errors"
                        @confirm="createDisposition"
                    />
                </aside>
            </div>
        </template>

        <Alert v-else variant="destructive">
            <FileWarning class="size-4" aria-hidden="true" />
            <AlertTitle>Route tidak tersedia</AlertTitle>
            <AlertDescription>
                Payload produksi tidak memuat route. Fixture tidak diaktifkan
                karena halaman ini bukan mode pratinjau.
            </AlertDescription>
        </Alert>
    </main>
</template>
