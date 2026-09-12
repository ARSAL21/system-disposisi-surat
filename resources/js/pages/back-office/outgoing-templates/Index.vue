<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Archive,
    FileUp,
    LayoutTemplate,
    Plus,
    QrCode,
    RefreshCw,
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
import type { OutgoingTemplate, OutgoingTemplatesPageProps } from '@/types';

const props = defineProps<OutgoingTemplatesPageProps>();
const createOpen = ref(false);
const createFile = ref<File | null>(null);
const createForm = ref({
    unit: props.units[0]?.code ?? '',
    code: '',
    name: '',
    qrPageMode: 'LAST_PAGE',
    qrPageNumber: '1',
    x: '0.72',
    y: '0.82',
    width: '0.18',
    height: '0.12',
});
const versionFiles = ref<Record<string, File | null>>({});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Template Surat',
                href: '/back-office/outgoing-templates',
            },
        ],
    },
});

const canManage = computed(() => props.units.length > 0);

function addQr(
    form: FormData,
    values: {
        qrPageMode: string;
        qrPageNumber: string;
        x: string;
        y: string;
        width: string;
        height: string;
    },
): void {
    form.append('qr_page_mode', values.qrPageMode);

    if (values.qrPageMode === 'SPECIFIC_PAGE') {
        form.append('qr_page_number', values.qrPageNumber);
    }

    form.append('qr_x_ratio', values.x);
    form.append('qr_y_ratio', values.y);
    form.append('qr_width_ratio', values.width);
    form.append('qr_height_ratio', values.height);
}

function createTemplate(): void {
    if (!createFile.value) {
        return;
    }

    const form = new FormData();
    form.append('organizational_unit_code', createForm.value.unit);
    form.append('code', createForm.value.code);
    form.append('name', createForm.value.name);
    form.append('document', createFile.value);
    addQr(form, createForm.value);
    router.post(props.routes.store, form, {
        forceFormData: true,
        onSuccess: () => {
            createOpen.value = false;
        },
    });
}

function uploadVersion(template: OutgoingTemplate): void {
    const file = versionFiles.value[template.public_id];

    if (!file) {
        return;
    }

    const form = new FormData();
    form.append('document', file);
    addQr(form, {
        qrPageMode: 'LAST_PAGE',
        qrPageNumber: '',
        x: '0.72',
        y: '0.82',
        width: '0.18',
        height: '0.12',
    });

    if (template.links.version_store) {
        router.post(template.links.version_store, form, {
            forceFormData: true,
        });
    }
}

function setActive(template: OutgoingTemplate): void {
    if (template.links.status_update) {
        router.patch(
            template.links.status_update,
            { is_active: !template.is_active },
            { preserveScroll: true },
        );
    }
}

function formatSize(value: number): string {
    return `${(value / 1024 / 1024).toFixed(2)} MB`;
}
</script>

<template>
    <Head title="Template Surat Keluar" />
    <main class="flex flex-1 flex-col bg-muted/15 p-4 sm:p-6 lg:p-8">
        <section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <header
                class="relative overflow-hidden rounded-[2rem] border bg-card p-6 shadow-sm sm:p-8"
            >
                <div
                    class="absolute -top-10 -right-10 size-56 rounded-full bg-emerald-500/10 blur-3xl"
                />
                <div
                    class="relative grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end"
                >
                    <div class="max-w-3xl">
                        <div
                            class="flex items-center gap-2 text-emerald-700 dark:text-emerald-300"
                        >
                            <LayoutTemplate class="size-5" /><span
                                class="text-sm font-semibold"
                                >Pusat format surat per Bagian</span
                            >
                        </div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                            Template Surat
                        </h1>
                        <p
                            class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                        >
                            Kabag menjaga format DOCX resmi. Staf mengunduh
                            template, menulis di Word, lalu mengunggah PDF
                            konsep untuk diperiksa.
                        </p>
                    </div>
                    <Dialog v-model:open="createOpen">
                        <DialogTrigger as-child
                            ><Button :disabled="!canManage" class="rounded-xl"
                                ><Plus class="mr-2 size-4" />Template
                                baru</Button
                            ></DialogTrigger
                        >
                        <DialogContent
                            class="max-h-[90vh] overflow-y-auto sm:max-w-xl"
                        >
                            <DialogHeader
                                ><DialogTitle>Buat template Bagian</DialogTitle
                                ><DialogDescription
                                    >Unggah DOCX tanpa macro. Letak QR
                                    ditentukan sebagai rasio halaman agar
                                    konsisten saat
                                    penerbitan.</DialogDescription
                                ></DialogHeader
                            >
                            <form
                                class="grid gap-4 py-2"
                                @submit.prevent="createTemplate"
                            >
                                <label class="grid gap-2"
                                    ><Label>Bagian</Label
                                    ><select
                                        v-model="createForm.unit"
                                        class="h-10 rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option
                                            v-for="unit in units"
                                            :key="unit.code"
                                            :value="unit.code"
                                        >
                                            {{ unit.name }}
                                        </option>
                                    </select></label
                                >
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Kode template</Label
                                        ><Input
                                            v-model="createForm.code"
                                            placeholder="SURAT-UMUM"
                                            required /></label
                                    ><label class="grid gap-2"
                                        ><Label>Nama template</Label
                                        ><Input
                                            v-model="createForm.name"
                                            placeholder="Surat Dinas"
                                            required
                                    /></label>
                                </div>
                                <label class="grid gap-2"
                                    ><Label>File DOCX</Label
                                    ><Input
                                        type="file"
                                        accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                        required
                                        @change="
                                            createFile =
                                                (
                                                    $event.target as HTMLInputElement
                                                ).files?.[0] ?? null
                                        "
                                /></label>
                                <div class="rounded-2xl border bg-muted/30 p-4">
                                    <div
                                        class="mb-3 flex items-center gap-2 text-sm font-semibold"
                                    >
                                        <QrCode
                                            class="size-4 text-emerald-600"
                                        />Letak QR
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label
                                            class="grid gap-1.5 text-xs font-medium"
                                            >Halaman<select
                                                v-model="createForm.qrPageMode"
                                                class="h-9 rounded-md border bg-background px-2 text-sm"
                                            >
                                                <option value="LAST_PAGE">
                                                    Halaman terakhir
                                                </option>
                                                <option value="SPECIFIC_PAGE">
                                                    Halaman tertentu
                                                </option>
                                            </select></label
                                        ><label
                                            v-if="
                                                createForm.qrPageMode ===
                                                'SPECIFIC_PAGE'
                                            "
                                            class="grid gap-1.5 text-xs font-medium"
                                            >Nomor halaman<Input
                                                v-model="
                                                    createForm.qrPageNumber
                                                "
                                                type="number"
                                                min="1"
                                        /></label>
                                    </div>
                                    <div
                                        class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4"
                                    >
                                        <label class="grid gap-1.5 text-xs"
                                            >X<Input
                                                v-model="createForm.x"
                                                inputmode="decimal" /></label
                                        ><label class="grid gap-1.5 text-xs"
                                            >Y<Input
                                                v-model="createForm.y"
                                                inputmode="decimal" /></label
                                        ><label class="grid gap-1.5 text-xs"
                                            >Lebar<Input
                                                v-model="createForm.width"
                                                inputmode="decimal" /></label
                                        ><label class="grid gap-1.5 text-xs"
                                            >Tinggi<Input
                                                v-model="createForm.height"
                                                inputmode="decimal"
                                        /></label>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="createOpen = false"
                                        >Batal</Button
                                    ><Button type="submit"
                                        ><FileUp class="mr-2 size-4" />Simpan
                                        template</Button
                                    >
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </header>

            <div
                v-if="templates.length === 0"
                class="rounded-2xl border border-dashed bg-card p-10 text-center"
            >
                <Archive class="mx-auto size-8 text-muted-foreground" />
                <p class="mt-3 font-semibold">Belum ada template</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        canManage
                            ? 'Mulai dengan mengunggah format DOCX resmi Bagian Anda.'
                            : 'Hubungi Kabag untuk menyiapkan template surat resmi unit Anda.'
                    }}
                </p>
            </div>
            <div v-else class="grid gap-4 lg:grid-cols-2">
                <Card
                    v-for="template in templates"
                    :key="template.public_id"
                    class="overflow-hidden rounded-3xl border shadow-sm"
                >
                    <CardContent class="p-0"
                        ><div
                            class="flex items-start justify-between gap-4 p-5"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        variant="outline"
                                        class="font-mono text-[10px]"
                                        >{{ template.code }}</Badge
                                    ><Badge
                                        :variant="
                                            template.is_active
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        class="rounded-full"
                                        >{{
                                            template.is_active
                                                ? 'Aktif'
                                                : 'Tidak aktif'
                                        }}</Badge
                                    >
                                </div>
                                <h2 class="mt-3 text-lg font-semibold">
                                    {{ template.name }}
                                </h2>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ template.unit.name }}
                                </p>
                            </div>
                            <Button
                                v-if="template.can_manage"
                                variant="ghost"
                                size="icon"
                                :title="
                                    template.is_active
                                        ? 'Nonaktifkan template'
                                        : 'Aktifkan template'
                                "
                                @click="setActive(template)"
                                ><RefreshCw class="size-4"
                            /></Button>
                        </div>
                        <div
                            v-if="template.latest_version"
                            class="border-y bg-muted/20 px-5 py-4 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium"
                                    >Versi
                                    {{
                                        template.latest_version.version_number
                                    }}</span
                                ><a
                                    :href="
                                        template.latest_version.links.download
                                    "
                                    class="text-emerald-700 underline-offset-4 hover:underline dark:text-emerald-300"
                                    >Unduh DOCX</a
                                >
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ template.latest_version.original_filename }}
                                ·
                                {{
                                    formatSize(
                                        template.latest_version.size_bytes,
                                    )
                                }}
                            </p>
                            <p
                                class="mt-2 font-mono text-[10px] break-all text-muted-foreground"
                            >
                                SHA-256 {{ template.latest_version.sha256 }}
                            </p>
                        </div>
                        <form
                            v-if="template.can_manage"
                            class="flex flex-col gap-2 p-5 sm:flex-row"
                            @submit.prevent="uploadVersion(template)"
                        >
                            <Input
                                type="file"
                                accept=".docx"
                                class="text-xs"
                                @change="
                                    versionFiles[template.public_id] =
                                        ($event.target as HTMLInputElement)
                                            .files?.[0] ?? null
                                "
                            /><Button
                                type="submit"
                                variant="outline"
                                :disabled="!template.is_active"
                                ><FileUp class="mr-2 size-4" />Versi
                                baru</Button
                            >
                        </form></CardContent
                    >
                </Card>
            </div>
        </section>
    </main>
</template>
