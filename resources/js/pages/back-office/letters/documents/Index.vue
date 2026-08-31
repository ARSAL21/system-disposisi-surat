<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import CreateDocumentVersionDialog from '@/components/back-office/letters/documents/CreateDocumentVersionDialog.vue';
import CurrentDocumentCard from '@/components/back-office/letters/documents/CurrentDocumentCard.vue';
import DocumentHistoryHeader from '@/components/back-office/letters/documents/DocumentHistoryHeader.vue';
import DocumentVersionDetailDialog from '@/components/back-office/letters/documents/DocumentVersionDetailDialog.vue';
import DocumentVersionTimeline from '@/components/back-office/letters/documents/DocumentVersionTimeline.vue';
import { useDocumentVersions } from '@/composables/useDocumentVersions';
import { previewDocumentVersionHistory } from '@/lib/documentVersionPreview';
import type {
    DocumentVersionCapabilities,
    DocumentVersionHistoryResponse,
    DocumentVersionItem,
    DocumentVersionLetter,
    DocumentVersionRoutes,
} from '@/types';

const props = defineProps<{
    letter?: DocumentVersionLetter;
    versions?: DocumentVersionItem[];
    capabilities?: DocumentVersionCapabilities;
    next_version_number?: number;
    routes?: DocumentVersionRoutes;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Arsip Dokumen',
                href: '/back-office/documents',
            },
            { title: 'Histori Dokumen Resmi', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);

const emptyLetter: DocumentVersionLetter = {
    id: 0,
    agenda_number: '-',
    agenda_year: 0,
    subject: 'Histori dokumen tidak tersedia',
    status: 'REGISTERED',
    received_at: null,
};

const initialData = computed<DocumentVersionHistoryResponse>(() => {
    if (previewMode.value) {
        return previewDocumentVersionHistory;
    }

    if (props.letter && props.versions && props.routes) {
        return {
            letter: props.letter,
            versions: props.versions,
            capabilities: props.capabilities ?? { can_create_version: false },
            next_version_number:
                props.next_version_number ?? props.versions.length + 1,
            routes: props.routes,
        };
    }

    return {
        letter: emptyLetter,
        versions: [],
        capabilities: { can_create_version: false },
        next_version_number: 1,
        routes: {
            archive: '/back-office/documents',
            store: '',
        },
    };
});

const storeRoute = computed(() =>
    previewMode.value
        ? previewDocumentVersionHistory.routes.store
        : (props.routes?.store ?? null),
);

const {
    letter: activeLetter,
    versions: activeVersions,
    capabilities: activeCapabilities,
    nextVersionNumber,
    selectedDetailVersion,
    isDetailDialogOpen,
    isCreateDialogOpen,
    isUploading,
    createErrors,
    openDetail,
    closeDetail,
    openCreate,
    closeCreate,
    copyHash,
    submitCreate,
} = useDocumentVersions(initialData.value, previewMode, storeRoute);

const currentVersion = computed(() => {
    return (
        activeVersions.value.find((v) => v.is_current) ??
        activeVersions.value[0]
    );
});

watch(
    () => props.versions,
    (newVersions) => {
        if (newVersions && !previewMode.value) {
            activeVersions.value = [...newVersions];
        }
    },
);

watch(
    () => props.capabilities,
    (newCapabilities) => {
        if (newCapabilities && !previewMode.value) {
            activeCapabilities.value = { ...newCapabilities };
        }
    },
);

watch(
    () => props.next_version_number,
    (newNextVersionNumber) => {
        if (newNextVersionNumber && !previewMode.value) {
            nextVersionNumber.value = newNextVersionNumber;
        }
    },
);

watch(
    () => props.letter,
    (newLetter) => {
        if (newLetter && !previewMode.value) {
            activeLetter.value = { ...newLetter };
        }
    },
);
</script>

<template>
    <Head :title="`Histori Dokumen — Agenda ${activeLetter.agenda_number}`" />

    <main class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <!-- Header with Agenda Number, Title, Count & Action -->
        <DocumentHistoryHeader
            :letter="activeLetter"
            :total-versions="activeVersions.length"
            :current-version-number="currentVersion?.version_number ?? 1"
            :can-create-version="activeCapabilities.can_create_version"
            :preview="previewMode"
            @open-create="openCreate"
        />

        <div
            class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]"
        >
            <!-- Left Column: Current Active Document Card -->
            <section class="space-y-4" aria-label="Dokumen resmi aktif">
                <h2
                    class="text-base font-semibold tracking-tight text-foreground sm:text-lg"
                >
                    Dokumen Resmi Aktif
                </h2>
                <CurrentDocumentCard
                    v-if="currentVersion"
                    :document="currentVersion"
                    @view-detail="openDetail"
                    @copy-hash="copyHash"
                />
            </section>

            <!-- Right Column: Timeline of all versions (newest to oldest) -->
            <DocumentVersionTimeline
                :versions="activeVersions"
                @view-detail="openDetail"
                @copy-hash="copyHash"
            />
        </div>

        <!-- Detail Metadata Modal Dialog -->
        <DocumentVersionDetailDialog
            :open="isDetailDialogOpen"
            :document="selectedDetailVersion"
            @update:open="closeDetail"
            @copy-hash="copyHash"
        />

        <!-- Create Corrected Version Modal Dialog -->
        <CreateDocumentVersionDialog
            :open="isCreateDialogOpen"
            :next-version-number="nextVersionNumber"
            :processing="isUploading"
            :errors="createErrors"
            @update:open="closeCreate"
            @submit="submitCreate"
        />
    </main>
</template>
