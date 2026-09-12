<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    FileCheck2,
    GitBranch,
    Inbox,
    Layers3,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import ResponseStatusBadge from '@/components/back-office/letter-responses/ResponseStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { previewLetterResponseDossiers } from '@/lib/letterResponsePreview';
import type { LetterResponsePageProps } from '@/types';

const props = defineProps<LetterResponsePageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Dossier Balasan', href: '/back-office/letter-responses' },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const dossiers = computed(() =>
    previewMode.value ? previewLetterResponseDossiers : (props.dossiers ?? []),
);
const hasBackendData = computed(() => Boolean(props.dossiers));
const totalMandates = computed(() =>
    dossiers.value.reduce(
        (total, dossier) => total + dossier.mandates.length,
        0,
    ),
);
const totalOpen = computed(
    () => dossiers.value.filter((dossier) => dossier.status === 'OPEN').length,
);

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Dossier Balasan" />
    <main class="min-h-full bg-muted/20 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section
                class="relative overflow-hidden rounded-3xl border bg-gradient-to-br from-indigo-600 via-indigo-700 to-slate-900 px-5 py-7 text-white shadow-xl shadow-indigo-900/10 sm:px-8 sm:py-9"
            >
                <div
                    class="pointer-events-none absolute -top-20 -right-16 size-72 rounded-full bg-white/10 blur-3xl"
                    aria-hidden="true"
                />
                <div class="relative max-w-3xl">
                    <div
                        class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-[0.18em] text-indigo-100 uppercase"
                    >
                        <Sparkles class="size-4" /> Penyusunan berjenjang · M8.2
                    </div>
                    <h1
                        class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        Dossier balasan resmi
                    </h1>
                    <p
                        class="mt-3 max-w-2xl text-sm leading-6 text-indigo-100 sm:text-base"
                    >
                        Baca hasil teknis setiap Bagian, tinjau proposal
                        Asisten, lalu siapkan mandat balasan dalam satu alur
                        yang mudah diikuti.
                    </p>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-3">
                <Card class="border-indigo-100/80 dark:border-indigo-950"
                    ><CardContent class="flex items-center gap-4 p-4"
                        ><span
                            class="grid size-10 place-items-center rounded-xl bg-indigo-500/10 text-indigo-600"
                            ><Inbox class="size-5"
                        /></span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Dossier terbuka
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ totalOpen }}
                            </p>
                        </div></CardContent
                    ></Card
                >
                <Card
                    ><CardContent class="flex items-center gap-4 p-4"
                        ><span
                            class="grid size-10 place-items-center rounded-xl bg-emerald-500/10 text-emerald-600"
                            ><FileCheck2 class="size-5"
                        /></span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Mandat tersedia
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ totalMandates }}
                            </p>
                        </div></CardContent
                    ></Card
                >
                <Card
                    ><CardContent class="flex items-center gap-4 p-4"
                        ><span
                            class="grid size-10 place-items-center rounded-xl bg-amber-500/10 text-amber-600"
                            ><Layers3 class="size-5"
                        /></span>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                Total dossier
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                {{ dossiers.length }}
                            </p>
                        </div></CardContent
                    ></Card
                >
            </section>

            <section class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase"
                    >
                        Ruang kerja
                    </p>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">
                        Surat yang membutuhkan penyusunan balasan
                    </h2>
                </div>
                <Badge variant="outline" class="rounded-full"
                    >{{ dossiers.length }} surat</Badge
                >
            </section>

            <section v-if="dossiers.length" class="grid gap-4 lg:grid-cols-2">
                <Card
                    v-for="dossier in dossiers"
                    :key="dossier.public_id"
                    class="group overflow-hidden border-border/80 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg"
                >
                    <CardContent class="p-5 sm:p-6"
                        ><div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        class="rounded-full"
                                        >{{
                                            dossier.letter.source === 'MANUAL'
                                                ? 'Manual'
                                                : 'Online'
                                        }}</Badge
                                    ><ResponseStatusBadge
                                        :status="dossier.status"
                                    />
                                </div>
                                <h3
                                    class="mt-3 text-lg leading-snug font-semibold"
                                >
                                    {{ dossier.letter.subject }}
                                </h3>
                                <p
                                    class="mt-1 truncate text-sm text-muted-foreground"
                                >
                                    {{
                                        dossier.letter.sender_organization_name
                                    }}
                                </p>
                            </div>
                            <span
                                class="grid size-10 shrink-0 place-items-center rounded-xl bg-muted text-muted-foreground transition group-hover:bg-indigo-600 group-hover:text-white"
                                ><GitBranch class="size-5"
                            /></span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-xl bg-muted/30 p-3">
                                <p class="text-xs text-muted-foreground">
                                    Nomor agenda
                                </p>
                                <p
                                    class="mt-1 truncate font-mono text-xs font-semibold"
                                >
                                    {{ dossier.letter.agenda_number }}
                                </p>
                            </div>
                            <div class="rounded-xl bg-muted/30 p-3">
                                <p class="text-xs text-muted-foreground">
                                    Pembaruan terakhir
                                </p>
                                <p class="mt-1 text-xs font-semibold">
                                    {{ formatDate(dossier.updated_at) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="mt-5 flex items-center justify-between gap-4"
                        >
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span class="text-muted-foreground"
                                        >Progres
                                        {{ dossier.progress.completed }}/{{
                                            dossier.progress.total
                                        }}
                                        cabang</span
                                    ><span
                                        class="font-semibold text-indigo-700 dark:text-indigo-300"
                                        >{{ dossier.progress.percent }}%</span
                                    >
                                </div>
                                <div
                                    class="mt-2 h-2 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-indigo-600 transition-all"
                                        :style="{
                                            width: `${dossier.progress.percent}%`,
                                        }"
                                    />
                                </div>
                            </div>
                            <Button as-child class="shrink-0 rounded-full"
                                ><Link :href="dossier.links.detail"
                                    >Lihat proses
                                    <ArrowUpRight class="ml-2 size-4" /></Link
                            ></Button>
                        </div>
                        <p class="mt-4 text-xs text-muted-foreground">
                            {{ dossier.assistants_count }} Asisten terlibat ·
                            {{ dossier.mandates.length }} mandat aktif
                        </p></CardContent
                    >
                </Card>
            </section>

            <Card v-else class="border-dashed"
                ><CardContent class="mx-auto max-w-lg p-10 text-center"
                    ><span
                        class="mx-auto grid size-12 place-items-center rounded-2xl bg-indigo-500/10 text-indigo-600"
                        ><GitBranch class="size-6"
                    /></span>
                    <h2 class="mt-4 text-lg font-semibold">
                        Belum ada dossier balasan
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        {{
                            hasBackendData
                                ? 'Belum ada surat yang siap disusun menjadi balasan.'
                                : 'Backend belum mengirimkan data dossier. Halaman produksi tidak menampilkan data contoh.'
                        }}
                    </p></CardContent
                ></Card
            >
        </div>
    </main>
</template>
