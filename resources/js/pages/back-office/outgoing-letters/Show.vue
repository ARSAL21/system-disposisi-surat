<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CircleCheck, Eye, FileWarning, Hash } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import OutgoingDocumentPanel from '@/components/back-office/outgoing-letters/OutgoingDocumentPanel.vue';
import OutgoingLetterActionCenter from '@/components/back-office/outgoing-letters/OutgoingLetterActionCenter.vue';
import OutgoingLetterActionDialog from '@/components/back-office/outgoing-letters/OutgoingLetterActionDialog.vue';
import OutgoingLetterHistory from '@/components/back-office/outgoing-letters/OutgoingLetterHistory.vue';
import OutgoingLetterOverview from '@/components/back-office/outgoing-letters/OutgoingLetterOverview.vue';
import OutgoingLetterStatusBadge from '@/components/back-office/outgoing-letters/OutgoingLetterStatusBadge.vue';
import OutgoingPublicationRail from '@/components/back-office/outgoing-letters/OutgoingPublicationRail.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { previewOutgoingLetterDetailFor } from '@/lib/outgoingLetterPreview';
import type { OutgoingLetterDetail, OutgoingLetterDetailPageProps, OutgoingLetterUiAction } from '@/types';

const props = defineProps<OutgoingLetterDetailPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Register Surat Keluar', href: '/back-office/outgoing-letters' },
            { title: 'Detail penerbitan', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const page = usePage();
const previewPublicId = computed(() => page.url.split('?')[0].split('/').filter(Boolean).at(-1) ?? '');
const simulatedLetter = ref<OutgoingLetterDetail | null>(null);
const letter = computed(() => simulatedLetter.value ?? (previewMode.value ? previewOutgoingLetterDetailFor(previewPublicId.value) : props.outgoingLetter ?? null));
const activeAction = ref<OutgoingLetterUiAction | null>(null);
const dialogOpen = ref(false);

function openAction(action: OutgoingLetterUiAction): void {
    activeAction.value = action;
    dialogOpen.value = true;
}

function completed(action: OutgoingLetterUiAction['kind'], payload: Record<string, string | File | null>): void {
    if (!previewMode.value || !letter.value) {
        toast.success('Tahap penerbitan berhasil diperbarui.');

        return;
    }

    const next = structuredClone(letter.value);
    const now = '2026-09-06T14:30:00+08:00';

    if (action === 'assign_number') {
        next.status = 'NUMBER_ASSIGNED';
        next.numbering.outgoing_number = String(payload.outgoing_number || '005/1201/SETDA/2026');
        next.numbering.letter_date = String(payload.letter_date || '2026-09-06');
        next.capabilities.can_assign_number = false;
        next.capabilities.can_withdraw = false;
        next.capabilities.can_upload_signed_document = true;
    } else if (action === 'upload_signed_document') {
        next.status = 'SIGNED_DOCUMENT_UPLOADED';
        next.capabilities.can_upload_signed_document = false;
        next.capabilities.can_verify = true;
        next.capabilities.can_request_document_revision = true;
    } else if (action === 'verify') {
        next.status = 'ADMIN_VERIFIED';
        next.verification.verified_at = now;
        next.verification.verified_by = 'Kepala Bagian Umum';
        next.capabilities.can_verify = false;
        next.capabilities.can_request_document_revision = false;
        next.capabilities.can_deliver = true;
    } else if (action === 'request_document_revision') {
        next.capabilities.can_verify = false;
        next.capabilities.can_request_document_revision = false;
        next.capabilities.can_upload_signed_document = true;
    } else if (action === 'deliver') {
        next.status = 'DELIVERED';
        next.delivery.delivered_at = now;
        next.delivery.method = next.source === 'ONLINE' ? 'PORTAL' : 'IN_PERSON';
        next.capabilities.can_deliver = false;
    } else if (action === 'withdraw') {
        next.status = 'WITHDRAWN';
        next.withdrawal = { reason: String(payload.withdrawal_reason), withdrawn_by: 'Sekretaris Daerah', withdrawn_at: now };
        next.capabilities.can_assign_number = false;
        next.capabilities.can_withdraw = false;
    }

    simulatedLetter.value = next;
    toast.success('Simulasi tahap berhasil diperbarui. Data backend tidak berubah.');
}

function documentAction(action: 'preview' | 'download', url: string | null): void {
    if (previewMode.value) {
        toast.info(action === 'preview' ? 'Pratinjau PDF privat aktif setelah backend M8.3 terhubung.' : 'Unduhan fixture dinonaktifkan.');

        return;
    }

    if (url) {
        window.location.assign(url);
    }
}
</script>

<template>
    <Head :title="letter ? `Surat Keluar · ${letter.numbering.outgoing_number ?? 'Belum bernomor'}` : 'Detail Surat Keluar'" />
    <main class="flex flex-1 flex-col bg-muted/15 p-4 sm:p-6 lg:p-8">
        <div v-if="letter" class="mx-auto flex w-full max-w-[100rem] flex-col gap-5">
            <header class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
                <Button as-child variant="ghost" size="sm" class="-ml-3 rounded-xl"><Link :href="letter.routes.index"><ArrowLeft class="size-4" /> Kembali ke register</Link></Button>
                <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0 max-w-4xl"><div class="flex flex-wrap items-center gap-2"><OutgoingLetterStatusBadge :status="letter.status" /><Badge variant="outline" class="rounded-full">{{ letter.source === 'ONLINE' ? 'Online' : 'Manual' }}</Badge></div><h1 class="mt-3 text-2xl font-semibold leading-tight tracking-tight sm:text-3xl">{{ letter.subject }}</h1><p class="mt-2 flex items-center gap-2 font-mono text-sm text-muted-foreground"><Hash class="size-4" />{{ letter.numbering.outgoing_number ?? 'Nomor resmi belum diberikan' }}</p></div>
                    <Button v-if="previewMode && letter.source === 'ONLINE'" type="button" variant="outline" class="min-h-11 rounded-xl" @click="toast.info('Gunakan akun publik lalu buka /public/previews/response-tracker untuk memeriksa tampilan pemohon.')"><Eye class="size-4" /> Cara cek tampilan pemohon</Button>
                </div>
            </header>

            <OutgoingPublicationRail :status="letter.status" />
            <OutgoingLetterOverview :letter="letter" />
            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_23rem]">
                <div class="min-w-0 space-y-5"><OutgoingDocumentPanel :letter="letter" :preview="previewMode" @document="documentAction" /><OutgoingLetterHistory :entries="letter.history" /></div>
                <aside class="space-y-5 xl:sticky xl:top-6"><OutgoingLetterActionCenter :letter="letter" @action="openAction" /><Alert><CircleCheck class="size-4" /><AlertTitle>Boundary penerbitan</AlertTitle><AlertDescription>Persetujuan pada layar ini adalah verifikasi administratif, bukan TTE tersertifikasi.</AlertDescription></Alert></aside>
            </div>
        </div>
        <Alert v-else variant="destructive"><FileWarning class="size-4" /><AlertTitle>Surat keluar tidak tersedia</AlertTitle><AlertDescription>Backend tidak mengirimkan resource surat keluar dan fixture hanya aktif pada route pratinjau.</AlertDescription></Alert>
    </main>

    <OutgoingLetterActionDialog v-model:open="dialogOpen" :action="activeAction" :preview="previewMode" @completed="completed" />
</template>
