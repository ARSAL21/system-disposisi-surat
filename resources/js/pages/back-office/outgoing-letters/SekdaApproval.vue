<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, FileWarning } from '@lucide/vue';
import { computed, ref } from 'vue';
import SekdaApprovalActionPanel from '@/components/back-office/outgoing-letters/SekdaApprovalActionPanel.vue';
import SekdaApprovalDocument from '@/components/back-office/outgoing-letters/SekdaApprovalDocument.vue';
import SekdaApprovalHeader from '@/components/back-office/outgoing-letters/SekdaApprovalHeader.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { previewSekdaApproval } from '@/lib/outgoingLetterPreview';
import type { SekdaApprovalDetail, SekdaApprovalPageProps } from '@/types';

const props = defineProps<SekdaApprovalPageProps>();
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Register Surat Keluar',
                href: '/back-office/outgoing-letters',
            },
            { title: 'Pengesahan Sekda', href: '#' },
        ],
    },
});
const previewMode = computed(() => props.preview === true);
const page = usePage();
const previewId = computed(() =>
    page.url.split('?')[0].split('/').filter(Boolean).at(-1),
);
const simulated = ref<SekdaApprovalDetail | null>(null);
const approval = computed(
    () =>
        simulated.value ??
        (previewMode.value && previewId.value
            ? previewSekdaApproval
            : (props.approval ?? null)),
);

function completed(
    action: 'qr' | 'manual' | 'upload_scan' | 'review_scan' | 'return',
): void {
    if (!previewMode.value || !approval.value) {
        return;
    }

    const next = structuredClone(approval.value);
    next.status =
        action === 'qr' || action === 'review_scan'
            ? 'READY_FOR_DELIVERY'
            : action === 'manual'
              ? 'AWAITING_MANUAL_SIGNATURE'
              : action === 'upload_scan'
                ? 'MANUAL_SCAN_REVIEW'
                : 'REVISION_REQUIRED';
    next.capabilities = {
        can_approve_qr: false,
        can_choose_manual_signature: false,
        can_upload_manual_scan: next.status === 'AWAITING_MANUAL_SIGNATURE',
        can_review_manual_scan: next.status === 'MANUAL_SCAN_REVIEW',
        can_return_for_revision: next.status !== 'READY_FOR_DELIVERY',
    };
    simulated.value = next;
}
</script>

<template>
    <Head title="Pengesahan Surat Sekda" />
    <main class="flex flex-1 flex-col bg-muted/15 p-4 sm:p-6 lg:p-8">
        <div
            v-if="approval"
            class="mx-auto flex w-full max-w-6xl flex-col gap-5"
        >
            <Button
                as-child
                variant="ghost"
                size="sm"
                class="-ml-3 w-fit rounded-xl"
                ><Link :href="approval.routes.index"
                    ><ArrowLeft class="size-4" />Kembali ke register</Link
                ></Button
            ><SekdaApprovalHeader :approval="approval" />
            <div
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]"
            >
                <SekdaApprovalDocument
                    :approval="approval"
                    :preview="previewMode"
                />
                <aside class="xl:sticky xl:top-6">
                    <SekdaApprovalActionPanel
                        :approval="approval"
                        :preview="previewMode"
                        @completed="completed"
                    />
                </aside>
            </div>
        </div>
        <Alert v-else variant="destructive" class="mx-auto w-full max-w-2xl"
            ><FileWarning class="size-4" /><AlertTitle
                >Surat tidak tersedia untuk pengesahan</AlertTitle
            ><AlertDescription
                >Data hanya tersedia untuk surat mandiri bernomor dalam scope
                pengesahan Sekda.</AlertDescription
            ></Alert
        >
    </main>
</template>
