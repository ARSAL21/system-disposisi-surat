<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileWarning } from '@lucide/vue';
import { computed, watch } from 'vue';
import ApprovalAuthorityCard from '@/components/back-office/intake/approval/ApprovalAuthorityCard.vue';
import ApprovalDecisionPanel from '@/components/back-office/intake/approval/ApprovalDecisionPanel.vue';
import ApprovalDetailHeader from '@/components/back-office/intake/approval/ApprovalDetailHeader.vue';
import LatestDecisionCard from '@/components/back-office/intake/approval/LatestDecisionCard.vue';
import RegisterDecisionDialog from '@/components/back-office/intake/approval/RegisterDecisionDialog.vue';
import RegistrationSummaryCard from '@/components/back-office/intake/approval/RegistrationSummaryCard.vue';
import RejectDecisionDialog from '@/components/back-office/intake/approval/RejectDecisionDialog.vue';
import ReturnDecisionDialog from '@/components/back-office/intake/approval/ReturnDecisionDialog.vue';
import StaffScreeningSummary from '@/components/back-office/intake/approval/StaffScreeningSummary.vue';
import SenderContactCard from '@/components/back-office/intake/SenderContactCard.vue';
import SubmissionDocumentReview from '@/components/back-office/intake/SubmissionDocumentReview.vue';
import SubmissionOverviewCard from '@/components/back-office/intake/SubmissionOverviewCard.vue';
import SubmissionTimeline from '@/components/back-office/intake/SubmissionTimeline.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useApprovalDecisions } from '@/composables/useApprovalDecisions';
import {
    previewApprovalDetail,
    previewSenderOrganizations,
} from '@/lib/intakeApprovalPreview';
import { approvalRoutes } from '@/lib/intakeApprovalPresentation';
import type { ApprovalSubmission, SenderOrganizationOption } from '@/types';

const props = defineProps<{
    submission?: ApprovalSubmission;
    senderOrganizations?: SenderOrganizationOption[];
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Persetujuan Surat', href: approvalRoutes.index },
            { title: 'Keputusan Administratif', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview ?? !props.submission);
const senderOrganizations = computed(
    () => props.senderOrganizations ?? previewSenderOrganizations,
);
const {
    submission: activeSubmission,
    returnDialogOpen,
    rejectDialogOpen,
    registerDialogOpen,
    processing,
    errors,
    decide,
    register,
} = useApprovalDecisions(
    props.submission ?? previewApprovalDetail,
    previewMode,
    () => senderOrganizations.value,
);

watch(
    () => props.submission,
    (submission) => {
        if (submission && !previewMode.value) activeSubmission.value = submission;
    },
);
</script>

<template>
    <Head :title="`Keputusan ${activeSubmission.subject}`" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <ApprovalDetailHeader
            :submission="activeSubmission"
            :preview="previewMode"
        />

        <div
            class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(20rem,0.85fr)]"
        >
            <section class="grid gap-5" aria-label="Isi surat untuk diputuskan">
                <SubmissionOverviewCard :submission="activeSubmission" />
                <SubmissionDocumentReview
                    v-if="activeSubmission.document"
                    :document="activeSubmission.document"
                    :preview-url="activeSubmission.links.document_preview"
                    :download-url="activeSubmission.links.document_download"
                    :can-download="
                        activeSubmission.capabilities.can_download_document
                    "
                    :preview-mode="previewMode"
                />
                <Alert v-else variant="destructive">
                    <FileWarning class="size-4" aria-hidden="true" />
                    <AlertTitle>Dokumen pengajuan tidak tersedia</AlertTitle>
                    <AlertDescription>
                        Surat tidak dapat diregistrasikan sebelum masalah
                        dokumen diperiksa oleh administrator sistem.
                    </AlertDescription>
                </Alert>
                <StaffScreeningSummary
                    :review="activeSubmission.screening_review"
                />
                <ApprovalDecisionPanel
                    v-if="activeSubmission.capabilities.can_decide"
                    :can-decide="activeSubmission.capabilities.can_decide"
                    :has-document="Boolean(activeSubmission.document)"
                    @return-to-staff="returnDialogOpen = true"
                    @reject="rejectDialogOpen = true"
                    @register="registerDialogOpen = true"
                />
                <RegistrationSummaryCard
                    v-if="activeSubmission.registration"
                    :registration="activeSubmission.registration"
                />
                <LatestDecisionCard
                    v-else-if="activeSubmission.latest_decision"
                    :decision="activeSubmission.latest_decision"
                />
            </section>

            <aside class="grid gap-5" aria-label="Konteks keputusan">
                <ApprovalAuthorityCard />
                <SenderContactCard :submission="activeSubmission" />
                <SubmissionTimeline :items="activeSubmission.timeline" />
            </aside>
        </div>

        <ReturnDecisionDialog
            v-model:open="returnDialogOpen"
            :processing="processing"
            :note-error="errors.note"
            @confirm="decide($event, 'return')"
        />
        <RejectDecisionDialog
            v-model:open="rejectDialogOpen"
            :processing="processing"
            :note-error="errors.note"
            @confirm="decide($event, 'reject')"
        />
        <RegisterDecisionDialog
            v-model:open="registerDialogOpen"
            :sender-name="activeSubmission.sender_organization_name"
            :organizations="senderOrganizations"
            :processing="processing"
            :errors="errors"
            @confirm="register"
        />
    </main>
</template>
