<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { FileWarning } from '@lucide/vue';
import { computed, ref } from 'vue';
import DispositionInstructionCard from '@/components/back-office/dispositions/DispositionInstructionCard.vue';
import RoutingDetailHeader from '@/components/back-office/routing/RoutingDetailHeader.vue';
import RoutingLetterOverviewCard from '@/components/back-office/routing/RoutingLetterOverviewCard.vue';
import RoutingOfficialDocumentCard from '@/components/back-office/routing/RoutingOfficialDocumentCard.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { previewDispositionInboxItems } from '@/lib/dispositionPreview';
import type { DispositionInboxItem, DispositionInboxRoutes } from '@/types';

const props = defineProps<{
    disposition?: DispositionInboxItem;
    routes?: DispositionInboxRoutes;
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
const indexUrl = computed(
    () =>
        (previewMode.value
            ? '/back-office/previews/dispositions/inbox'
            : props.routes?.index) ?? '/back-office/dispositions/inbox',
);
const interfaceNotice = ref('');

function handleDocumentAction(action: 'preview' | 'download'): void {
    interfaceNotice.value =
        action === 'preview'
            ? 'Pratinjau lokal tidak membuka PDF privat. Endpoint produksi akan memverifikasi penerima disposisi dan Position Assignment aktif.'
            : 'Unduhan fixture dinonaktifkan. Berkas produksi hanya dilayani melalui endpoint privat yang terotorisasi.';
}
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
