<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronRight,
    FilePlus2,
    FileText,
    Send,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    StandaloneOutgoingPageProps,
    StandaloneOutgoingStatus,
} from '@/types';

const props = defineProps<StandaloneOutgoingPageProps>();
const createOpen = ref(false);
const file = ref<File | null>(null);
const form = ref({
    unit: props.form_options.units[0]?.code ?? '',
    template: '',
    recipientName: '',
    recipientOrganization: '',
    recipientPosition: '',
    recipientAddress: '',
    recipientEmail: '',
    subject: '',
    summary: '',
    copies: [] as string[],
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Konsep Surat Keluar',
                href: '/back-office/standalone-outgoing',
            },
        ],
    },
});

const templatesForUnit = computed(() =>
    props.form_options.templates.filter(
        (template) =>
            template.unit_code === form.value.unit && template.latest_version,
    ),
);
const canCreate = computed(
    () =>
        props.form_options.units.length > 0 &&
        templatesForUnit.value.length > 0,
);

function formatDate(value: string | null): string {
    return value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';
}
function statusClass(status: StandaloneOutgoingStatus): string {
    return {
        DRAFT: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        SECTION_REVIEW:
            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        REVISION_REQUIRED:
            'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        ASSISTANT_REVIEW:
            'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
        AWAITING_NUMBER:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    }[status];
}

function submit(): void {
    if (!file.value || !form.value.template) {
        return;
    }

    const payload = new FormData();
    payload.append('organizational_unit_code', form.value.unit);
    payload.append(
        'outgoing_letter_template_version_public_id',
        form.value.template,
    );
    payload.append('recipient_name', form.value.recipientName);
    payload.append('recipient_organization', form.value.recipientOrganization);
    payload.append('recipient_position', form.value.recipientPosition);
    payload.append('recipient_address', form.value.recipientAddress);
    payload.append('recipient_email', form.value.recipientEmail);
    payload.append('subject', form.value.subject);
    payload.append('summary', form.value.summary);
    form.value.copies.forEach((code) =>
        payload.append('copy_position_codes[]', code),
    );
    payload.append('document', file.value);
    router.post(props.routes.store, payload, {
        forceFormData: true,
        onSuccess: () => {
            createOpen.value = false;
        },
    });
}

function chooseUnit(): void {
    form.value.template =
        templatesForUnit.value[0]?.latest_version?.public_id ?? '';
}
</script>

<template>
    <Head title="Konsep Surat Keluar" />
    <main class="flex flex-1 flex-col bg-muted/15 p-4 sm:p-6 lg:p-8">
        <section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <header
                class="overflow-hidden rounded-[2rem] border bg-card shadow-sm"
            >
                <div
                    class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end"
                >
                    <div class="max-w-3xl">
                        <div
                            class="flex items-center gap-2 text-violet-700 dark:text-violet-300"
                        >
                            <FileText class="size-5" /><span
                                class="text-sm font-semibold"
                                >Surat keluar mandiri</span
                            >
                        </div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                            Konsep Surat Keluar
                        </h1>
                        <p
                            class="mt-2 text-sm leading-6 text-muted-foreground sm:text-base"
                        >
                            Satu alur sederhana: staf menyiapkan konsep, Kabag
                            memeriksa, lalu Asisten memeriksa sebelum surat siap
                            diberi nomor.
                        </p>
                    </div>
                    <Dialog v-model:open="createOpen"
                        ><DialogTrigger as-child
                            ><Button :disabled="!canCreate" class="rounded-xl"
                                ><FilePlus2 class="mr-2 size-4" />Buat
                                konsep</Button
                            ></DialogTrigger
                        ><DialogContent
                            class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
                            ><DialogHeader
                                ><DialogTitle>Buat konsep surat</DialogTitle
                                ><DialogDescription
                                    >Unduh format dari menu Template Surat,
                                    susun di Word, simpan sebagai PDF, kemudian
                                    unggah di sini.</DialogDescription
                                ></DialogHeader
                            >
                            <form
                                class="grid gap-4 py-2"
                                @submit.prevent="submit"
                            >
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Bagian penyusun</Label
                                        ><select
                                            v-model="form.unit"
                                            class="h-10 rounded-md border bg-background px-3 text-sm"
                                            @change="chooseUnit"
                                        >
                                            <option
                                                v-for="unit in form_options.units"
                                                :key="unit.code"
                                                :value="unit.code"
                                            >
                                                {{ unit.name }}
                                            </option>
                                        </select></label
                                    ><label class="grid gap-2"
                                        ><Label>Template</Label
                                        ><select
                                            v-model="form.template"
                                            class="h-10 rounded-md border bg-background px-3 text-sm"
                                            required
                                        >
                                            <option value="" disabled>
                                                Pilih template
                                            </option>
                                            <option
                                                v-for="template in templatesForUnit"
                                                :key="
                                                    template.latest_version
                                                        ?.public_id
                                                "
                                                :value="
                                                    template.latest_version
                                                        ?.public_id
                                                "
                                            >
                                                {{ template.name }} · v{{
                                                    template.latest_version
                                                        ?.version_number
                                                }}
                                            </option>
                                        </select></label
                                    >
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Nama penerima</Label
                                        ><Input
                                            v-model="form.recipientName"
                                            required /></label
                                    ><label class="grid gap-2"
                                        ><Label>Instansi penerima</Label
                                        ><Input
                                            v-model="
                                                form.recipientOrganization
                                            "
                                    /></label>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Jabatan penerima</Label
                                        ><Input
                                            v-model="
                                                form.recipientPosition
                                            " /></label
                                    ><label class="grid gap-2"
                                        ><Label>Email penerima (opsional)</Label
                                        ><Input
                                            v-model="form.recipientEmail"
                                            type="email"
                                    /></label>
                                </div>
                                <label class="grid gap-2"
                                    ><Label>Alamat penerima</Label
                                    ><textarea
                                        v-model="form.recipientAddress"
                                        class="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                    /></label
                                ><label class="grid gap-2"
                                    ><Label>Perihal</Label
                                    ><Input
                                        v-model="form.subject"
                                        required /></label
                                ><label class="grid gap-2"
                                    ><Label>Ringkasan untuk pemeriksa</Label
                                    ><textarea
                                        v-model="form.summary"
                                        class="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                    /></label
                                ><label class="grid gap-2"
                                    ><Label>Tembusan (opsional)</Label
                                    ><select
                                        v-model="form.copies"
                                        multiple
                                        class="min-h-28 rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option
                                            v-for="position in form_options.copy_positions"
                                            :key="position.code"
                                            :value="position.code"
                                        >
                                            {{ position.name
                                            }}{{
                                                position.unit_name
                                                    ? ` — ${position.unit_name}`
                                                    : ''
                                            }}
                                        </option></select
                                    ><span class="text-xs text-muted-foreground"
                                        >Tahan Ctrl atau Cmd untuk memilih
                                        beberapa jabatan.</span
                                    ></label
                                ><label class="grid gap-2"
                                    ><Label>PDF konsep surat</Label
                                    ><Input
                                        type="file"
                                        accept="application/pdf,.pdf"
                                        required
                                        @change="
                                            file =
                                                (
                                                    $event.target as HTMLInputElement
                                                ).files?.[0] ?? null
                                        "
                                /></label>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="createOpen = false"
                                        >Batal</Button
                                    ><Button type="submit"
                                        ><Send class="mr-2 size-4" />Simpan
                                        konsep</Button
                                    >
                                </div>
                            </form></DialogContent
                        ></Dialog
                    >
                </div>
                <div class="grid grid-cols-3 border-t bg-muted/20">
                    <div class="p-4 text-center text-xs">
                        <span
                            class="mx-auto mb-2 grid size-7 place-items-center rounded-full bg-violet-600 text-white"
                            >1</span
                        >Staf menyiapkan
                    </div>
                    <div class="border-x p-4 text-center text-xs">
                        <span
                            class="mx-auto mb-2 grid size-7 place-items-center rounded-full border bg-background"
                            >2</span
                        >Kabag memeriksa
                    </div>
                    <div class="p-4 text-center text-xs">
                        <span
                            class="mx-auto mb-2 grid size-7 place-items-center rounded-full border bg-background"
                            >3</span
                        >Asisten memeriksa
                    </div>
                </div>
            </header>
            <div
                v-if="drafts.data.length === 0"
                class="rounded-3xl border border-dashed bg-card p-12 text-center"
            >
                <FileText class="mx-auto size-9 text-muted-foreground" />
                <p class="mt-4 font-semibold">Belum ada konsep surat</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Konsep yang Anda buat akan muncul di meja kerja ini.
                </p>
            </div>
            <div v-else class="grid gap-3">
                <Card
                    v-for="draft in drafts.data"
                    :key="draft.public_id"
                    class="rounded-2xl border shadow-sm transition-shadow hover:shadow-md"
                    ><CardContent
                        class="grid gap-4 p-5 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center"
                        ><span
                            class="grid size-11 place-items-center rounded-2xl bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-200"
                            ><FileText class="size-5"
                        /></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge
                                    class="rounded-full"
                                    :class="statusClass(draft.status)"
                                    >{{ draft.status_label }}</Badge
                                ><span class="text-xs text-muted-foreground">{{
                                    draft.unit.name
                                }}</span>
                            </div>
                            <h2 class="mt-2 truncate font-semibold">
                                {{ draft.subject }}
                            </h2>
                            <p
                                class="mt-1 truncate text-sm text-muted-foreground"
                            >
                                Kepada {{ draft.recipient_name
                                }}{{
                                    draft.recipient_organization
                                        ? ` · ${draft.recipient_organization}`
                                        : ''
                                }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground">
                                Disusun {{ draft.created_by.name }} · diperbarui
                                {{ formatDate(draft.updated_at) }}
                            </p>
                        </div>
                        <Button as-child variant="outline" class="rounded-xl"
                            ><Link :href="draft.links.show"
                                >Buka proses
                                <ChevronRight
                                    class="ml-2 size-4" /></Link></Button></CardContent
                ></Card>
            </div>
            <div
                class="rounded-2xl border bg-card p-4 text-sm text-muted-foreground"
            >
                <UsersRound class="mr-2 inline size-4 text-violet-600" />Hanya
                Kabag Bagian yang sama dan Asisten induk Bagian yang dapat
                memeriksa konsep. Konsep yang dikembalikan selalu melalui
                pemeriksaan Kabag kembali.
            </div>
        </section>
    </main>
</template>
