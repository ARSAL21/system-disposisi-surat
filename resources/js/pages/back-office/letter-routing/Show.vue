<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { CircleCheck, FileWarning } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import InitialRouteReceiptCard from '@/components/back-office/routing/InitialRouteReceiptCard.vue';
import RoutingDecisionPanel from '@/components/back-office/routing/RoutingDecisionPanel.vue';
import RoutingDetailHeader from '@/components/back-office/routing/RoutingDetailHeader.vue';
import RoutingLetterOverviewCard from '@/components/back-office/routing/RoutingLetterOverviewCard.vue';
import RoutingOfficialDocumentCard from '@/components/back-office/routing/RoutingOfficialDocumentCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    previewExecutivePositions,
    previewLetterRoutingItems,
} from '@/lib/letterRoutingPreview';
import type {
    ExecutivePositionOption,
    InitialRouteReceipt,
    LetterRoutingCapabilities,
    LetterRoutingItem,
    LetterRoutingRoutes,
} from '@/types';

const props = defineProps<{
    letter?: LetterRoutingItem;
    executivePositions?: ExecutivePositionOption[];
    capabilities?: LetterRoutingCapabilities;
    routes?: LetterRoutingRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Routing Surat', href: '/back-office/letter-routing' },
            { title: 'Tinjau & Routing', href: '#' },
        ],
    },
});

const page = usePage();
const previewMode = computed(() => props.preview === true);
const previewLetterId = computed(() => {
    const match = page.url
        .split('?')[0]
        .match(/\/letter-routing\/letters\/(\d+)$/);

    return match ? Number(match[1]) : null;
});
const previewLetter = computed(
    () =>
        previewLetterRoutingItems.find(
            (letter) => letter.id === previewLetterId.value,
        ) ?? previewLetterRoutingItems[0],
);
const baseLetter = computed(() =>
    previewMode.value ? previewLetter.value : (props.letter ?? null),
);
const simulatedRoute = ref<InitialRouteReceipt | null>(null);
const activeLetter = computed<LetterRoutingItem | null>(() => {
    if (!baseLetter.value) {
        return null;
    }

    if (!simulatedRoute.value) {
        return baseLetter.value;
    }

    return {
        ...baseLetter.value,
        status: 'ROUTED',
        current_route: simulatedRoute.value,
    };
});
const executivePositions = computed(() =>
    previewMode.value
        ? previewExecutivePositions
        : (props.executivePositions ?? []),
);
const canRoute = computed(
    () =>
        previewMode.value ||
        (props.capabilities?.can_route === true &&
            activeLetter.value?.status === 'REGISTERED'),
);
const indexUrl = computed(
    () =>
        (previewMode.value
            ? '/back-office/previews/letter-routing'
            : props.routes?.index) ?? '/back-office/letter-routing',
);

const processing = ref(false);
const errors = ref<Record<string, string>>({});
const interfaceNotice = ref('');
const successNotice = ref('');
let previewTimer: ReturnType<typeof setTimeout> | null = null;

function handleDocumentAction(action: 'preview' | 'download'): void {
    interfaceNotice.value =
        action === 'preview'
            ? 'Pratinjau ini tidak membuka PDF privat. Endpoint berkas M5 akan dihubungkan setelah kontrak authorization tersedia.'
            : 'Unduhan dinonaktifkan pada fixture UI. Berkas produksi tetap harus dilayani endpoint privat yang terotorisasi.';
}

function routeLetter(targetPositionId: number): void {
    errors.value = {};
    successNotice.value = '';

    if (previewMode.value) {
        const target = executivePositions.value.find(
            (position) => position.id === targetPositionId,
        );

        if (!target) {
            errors.value = {
                target_position_id: 'Tujuan pimpinan tidak tersedia.',
            };

            return;
        }

        processing.value = true;
        previewTimer = setTimeout(() => {
            simulatedRoute.value = {
                status: 'PENDING',
                target_position: target,
                routed_by: {
                    name: 'La Ode Rahmat Hidayat',
                    position: 'Kepala Bagian Umum',
                    unit: 'Bagian Umum',
                },
                routed_at: '2026-08-31T10:24:00+08:00',
            };
            successNotice.value =
                'Simulasi UI selesai. Tidak ada data backend yang dibuat atau status surat yang diubah.';
            processing.value = false;
            previewTimer = null;
        }, 650);

        return;
    }

    if (!props.routes?.store) {
        errors.value = {
            target_position_id:
                'Endpoint routing belum tersedia. Muat ulang halaman setelah backend M5 diaktifkan.',
        };

        return;
    }

    router.post(
        props.routes.store,
        { target_position_id: targetPositionId },
        {
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
        },
    );
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
            activeLetter
                ? `Routing ${activeLetter.agenda_number}`
                : 'Detail Routing Surat'
        "
    />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <template v-if="activeLetter">
            <RoutingDetailHeader
                :letter="activeLetter"
                :back-href="indexUrl"
                back-label="Kembali ke antrean routing"
                :preview="previewMode"
            />

            <Alert
                v-if="successNotice"
                class="border-emerald-300 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/25"
            >
                <CircleCheck
                    class="size-4 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <AlertTitle>Routing ditampilkan pada mode simulasi</AlertTitle>
                <AlertDescription>{{ successNotice }}</AlertDescription>
            </Alert>

            <Alert v-if="interfaceNotice">
                <FileWarning class="size-4" aria-hidden="true" />
                <AlertTitle>Fixture UI tanpa akses berkas privat</AlertTitle>
                <AlertDescription>{{ interfaceNotice }}</AlertDescription>
            </Alert>

            <div
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(20rem,0.85fr)]"
            >
                <section class="grid gap-5" aria-label="Informasi surat">
                    <RoutingLetterOverviewCard :letter="activeLetter" />
                    <RoutingOfficialDocumentCard
                        :document="activeLetter.current_document"
                        :preview="previewMode"
                        @preview="handleDocumentAction('preview')"
                        @download="handleDocumentAction('download')"
                    />
                </section>

                <aside class="grid gap-5" aria-label="Keputusan routing">
                    <InitialRouteReceiptCard
                        v-if="activeLetter.current_route"
                        :route="activeLetter.current_route"
                    />
                    <RoutingDecisionPanel
                        v-else
                        :positions="executivePositions"
                        :can-route="canRoute"
                        :processing="processing"
                        :errors="errors"
                        @confirm="routeLetter"
                    />
                </aside>
            </div>
        </template>

        <Alert v-else variant="destructive">
            <FileWarning class="size-4" aria-hidden="true" />
            <AlertTitle>Data surat tidak tersedia</AlertTitle>
            <AlertDescription>
                Payload produksi tidak memuat surat. Fixture tidak diaktifkan
                karena halaman ini bukan mode pratinjau.
            </AlertDescription>
        </Alert>
    </main>
</template>
