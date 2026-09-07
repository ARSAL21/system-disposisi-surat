<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Gavel, Plus, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import ResponseActionDialog from '@/components/back-office/letter-responses/ResponseActionDialog.vue';
import ResponseInspector from '@/components/back-office/letter-responses/ResponseInspector.vue';
import ResponseProgressRail from '@/components/back-office/letter-responses/ResponseProgressRail.vue';
import ResponseStatusBadge from '@/components/back-office/letter-responses/ResponseStatusBadge.vue';
import ResponseTree from '@/components/back-office/letter-responses/ResponseTree.vue';
import ResponseWorkspaceHeader from '@/components/back-office/letter-responses/ResponseWorkspaceHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { previewLetterResponseDossier } from '@/lib/letterResponsePreview';
import type { LetterResponsePageProps, LetterResponseSelection, LetterResponseUiAction } from '@/types';

const props = defineProps<LetterResponsePageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Dossier Balasan', href: '/back-office/letter-responses' },
            { title: 'Detail proses', href: '#' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const dossier = computed(() => previewMode.value ? previewLetterResponseDossier : props.dossier ?? null);
const inspectorOpen = ref(true);
const selected = ref<LetterResponseSelection | null>(
    dossier.value ? { kind: 'executive', dossier: dossier.value } : null,
);
const activeAction = ref<LetterResponseUiAction | null>(null);
const actionOpen = ref(false);
const backHref = computed(() => previewMode.value ? '/back-office/previews/letter-responses' : (props.routes?.index ?? '/back-office/letter-responses'));
const hasBackendData = computed(() => Boolean(props.dossier));

const mandateSources = computed(() => {
    if (!dossier.value) {
return [];
}

    const proposals = dossier.value.assistants.flatMap((assistant) => {
        const proposal = assistant.proposal;

        return proposal?.status === 'READY' && proposal.current_version
            ? [{ value: proposal.current_version.public_id, label: `${assistant.position_name} · v${proposal.current_version.version_number}` }]
            : [];
    });
    const consolidation = dossier.value.consolidation;

    return consolidation?.status === 'READY' && consolidation.current_version
        ? [{ value: consolidation.current_version.public_id, label: `Konsolidasi eksekutif · v${consolidation.current_version.version_number}` }, ...proposals]
        : proposals;
});

const signatoryOptions = computed(() => (dossier.value?.eligible_signatories ?? []).map((position) => ({
    value: position.code,
    label: position.official_name ? `${position.name} · ${position.official_name}` : position.name,
})));

const proposalSourceIds = computed(() => dossier.value?.assistants.flatMap((assistant) => {
    const proposal = assistant.proposal;

    return proposal?.status === 'READY' && proposal.current_version ? [proposal.current_version.public_id] : [];
}) ?? []);

function openAction(action: LetterResponseUiAction): void {
    activeAction.value = action;
    actionOpen.value = true;
}

function selectNode(selection: LetterResponseSelection): void {
    selected.value = selection;
    inspectorOpen.value = true;
}
</script>

<template>
    <Head :title="dossier ? `Dossier · ${dossier.letter.agenda_number}` : 'Dossier Balasan'" />
    <div class="w-full flex-1 bg-muted/20 p-4 sm:p-6 lg:p-8">
        <div v-if="dossier" class="mx-auto max-w-[1500px] space-y-6">
            <ResponseWorkspaceHeader :dossier="dossier" :back-href="backHref" />
            <ResponseProgressRail :dossier="dossier" />

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] xl:grid-cols-[minmax(0,1fr)_26rem]">
                <!-- Left Column: Tree & Mandate Plan -->
                <section class="min-w-0 space-y-6">
                    <ResponseTree :dossier="dossier" :selected="selected" @select="selectNode" @action="openAction" />

                    <!-- Mandate Plan Card -->
                    <Card class="overflow-hidden border-indigo-100/80 bg-gradient-to-br from-background to-indigo-50/50 shadow-sm dark:border-indigo-950 dark:to-indigo-950/20">
                        <CardHeader class="flex flex-row items-start justify-between gap-4 pb-3">
                            <div>
                                <CardTitle class="flex items-center gap-2 text-lg">
                                    <Gavel class="size-5 text-indigo-600 dark:text-indigo-400" /> Rencana mandat balasan
                                </CardTitle>
                                <p class="mt-1 text-xs sm:text-sm text-muted-foreground leading-5">
                                    Mandat menentukan dokumen final yang diterbitkan dan siapa penandatangan substantifnya.
                                </p>
                            </div>
                            <Badge variant="outline" class="shrink-0 rounded-full font-semibold">
                                {{ dossier.mandates.length }} mandat
                            </Badge>
                        </CardHeader>
                        <CardContent class="space-y-4 pt-2">
                            <div v-if="dossier.mandates.length" class="grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="mandate in dossier.mandates"
                                    :key="mandate.public_id"
                                    class="rounded-2xl border bg-background p-4 shadow-xs"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold truncate">{{ mandate.subject }}</p>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                Versi {{ mandate.version_number }} · dibuat {{ mandate.created_at.slice(0, 10) }}
                                            </p>
                                        </div>
                                        <ResponseStatusBadge :status="mandate.status" />
                                    </div>
                                    <div class="mt-3.5 flex items-center gap-2 border-t pt-2.5 text-xs text-muted-foreground">
                                        <ShieldCheck class="size-4 shrink-0 text-emerald-600" />
                                        <span class="truncate">{{ mandate.signatory_name }} · {{ mandate.signatory_position }}</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="rounded-xl border border-dashed bg-muted/20 p-5 text-center text-sm text-muted-foreground"
                            >
                                Belum ada mandat aktif. Pilih proposal Asisten atau unggah konsolidasi eksekutif untuk memulai mandat.
                            </div>

                            <div v-if="dossier.viewer.can_authorize && dossier.status === 'OPEN'" class="flex flex-wrap gap-2 pt-1">
                                <Button
                                    v-if="dossier.links.mandate_store && mandateSources.length && signatoryOptions.length"
                                    type="button"
                                    class="rounded-full shadow-xs"
                                    @click="openAction({
                                        kind: 'mandate',
                                        route: dossier.links.mandate_store,
                                        title: 'Buat mandat balasan',
                                        description: 'Pilih dokumen final dan pejabat yang memperoleh mandat eksplisit untuk surat ini.',
                                        sources: mandateSources,
                                        signatories: signatoryOptions,
                                    })"
                                >
                                    <Plus class="mr-2 size-4" /> Buat mandat
                                </Button>
                                <Button
                                    v-if="dossier.links.consolidation_store && !dossier.consolidation"
                                    type="button"
                                    variant="outline"
                                    class="rounded-full"
                                    @click="openAction({
                                        kind: 'upload',
                                        route: dossier.links.consolidation_store,
                                        title: 'Unggah konsolidasi eksekutif',
                                        description: 'Unggah PDF hasil pilihan atau penggabungan usulan Asisten.',
                                        sourceVersionPublicIds: proposalSourceIds,
                                    })"
                                >
                                    Unggah konsolidasi
                                </Button>
                                <Button
                                    v-if="dossier.mandates.length && dossier.links.finalize"
                                    type="button"
                                    variant="ghost"
                                    class="rounded-full"
                                    @click="openAction({
                                        kind: 'finalize',
                                        route: dossier.links.finalize,
                                        title: 'Finalisasi rencana balasan',
                                        description: 'Pastikan seluruh mandat sudah benar. Rencana tidak dapat diubah setelah finalisasi.',
                                    })"
                                >
                                    Finalisasi rencana
                                </Button>
                            </div>
                            <p v-else class="text-xs leading-5 text-muted-foreground">
                                Tahap M8.3 melanjutkan mandat terotorisasi ini ke penomoran, tanda tangan, verifikasi administratif, dan pengiriman.
                            </p>
                        </CardContent>
                    </Card>
                </section>

                <!-- Right Column: Sticky Inspector -->
                <aside class="min-w-0">
                    <div class="mb-3 flex items-center justify-between lg:hidden">
                        <p class="text-sm font-semibold">Detail inspektor pilihan</p>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="rounded-full"
                            @click="inspectorOpen = !inspectorOpen"
                        >
                            {{ inspectorOpen ? 'Sembunyikan' : 'Tampilkan' }}
                            <ChevronUp v-if="inspectorOpen" class="ml-2 size-4" />
                            <ChevronDown v-else class="ml-2 size-4" />
                        </Button>
                    </div>
                    <ResponseInspector
                        v-if="inspectorOpen"
                        :dossier="dossier"
                        :selection="selected"
                        @action="openAction"
                    />
                </aside>
            </div>
        </div>

        <Card v-else class="mx-auto mt-10 max-w-xl border-dashed">
            <CardContent class="p-10 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-amber-500/10 text-amber-600">
                    <Gavel class="size-6" />
                </span>
                <h1 class="mt-4 text-lg font-semibold">Dossier tidak tersedia</h1>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    {{ hasBackendData ? 'Surat yang diminta belum memiliki dossier balasan.' : 'Backend belum mengirimkan dossier untuk halaman ini. Data contoh hanya tersedia dalam mode preview.' }}
                </p>
                <Button as-child variant="outline" class="mt-5 rounded-full">
                    <Link :href="backHref">Kembali ke daftar</Link>
                </Button>
            </CardContent>
        </Card>

        <ResponseActionDialog
            v-model:open="actionOpen"
            :action="activeAction"
            :preview="previewMode"
            @completed="toast.success($event)"
        />
    </div>
</template>
