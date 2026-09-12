<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    Clock3,
    Download,
    FileUp,
    RotateCcw,
    Send,
    UserCheck,
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { StandaloneOutgoingShowPageProps } from '@/types';

const props = defineProps<StandaloneOutgoingShowPageProps>();
const replacement = ref<File | null>(null);
const replacementNote = ref('');
const reviewReason = ref('');
const editOpen = ref(false);
const editForm = ref({
    template: props.draft.template_version_public_id,
    recipientName: props.draft.recipient.name,
    recipientOrganization: props.draft.recipient.organization ?? '',
    recipientPosition: props.draft.recipient.position ?? '',
    recipientAddress: props.draft.recipient.address ?? '',
    recipientEmail: props.draft.recipient.email ?? '',
    subject: props.draft.subject,
    summary: props.draft.summary ?? '',
    copies: props.draft.copy_recipients.map((recipient) => recipient.code),
});
const templatesForDraftUnit = computed(() =>
    props.form_options.templates.filter(
        (template) =>
            template.unit_code === props.draft.unit.code &&
            template.latest_version,
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Konsep Surat Keluar',
                href: '/back-office/standalone-outgoing',
            },
            { title: 'Proses konsep', href: '' },
        ],
    },
});

function formatDate(value: string | null): string {
    return value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'full',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';
}
function size(value: number): string {
    return `${(value / 1024 / 1024).toFixed(2)} MB`;
}
function submitForReview(): void {
    if (props.draft.actions.submit) {
        router.post(props.draft.actions.submit);
    }
}
function uploadVersion(): void {
    if (!props.draft.actions.document_version || !replacement.value) {
        return;
    }

    const form = new FormData();
    form.append('document', replacement.value);
    form.append('revision_note', replacementNote.value);
    router.post(props.draft.actions.document_version, form, {
        forceFormData: true,
    });
}
function review(url: string | null, decision: 'APPROVED' | 'RETURNED'): void {
    if (!url) {
        return;
    }

    router.post(url, {
        decision,
        reason: decision === 'RETURNED' ? reviewReason.value : null,
    });
}

function updateDraft(): void {
    if (!props.draft.actions.update) {
        return;
    }

    router.put(
        props.draft.actions.update,
        {
            outgoing_letter_template_version_public_id: editForm.value.template,
            recipient_name: editForm.value.recipientName,
            recipient_organization:
                editForm.value.recipientOrganization || null,
            recipient_position: editForm.value.recipientPosition || null,
            recipient_address: editForm.value.recipientAddress || null,
            recipient_email: editForm.value.recipientEmail || null,
            subject: editForm.value.subject,
            summary: editForm.value.summary || null,
            copy_position_codes: editForm.value.copies,
        },
        {
            onSuccess: () => {
                editOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="draft.subject" />
    <main class="flex flex-1 flex-col bg-muted/15 p-4 sm:p-6 lg:p-8">
        <section class="mx-auto flex w-full max-w-6xl flex-col gap-5">
            <div class="flex items-center justify-between gap-3">
                <Button as-child variant="ghost" class="-ml-3"
                    ><Link href="/back-office/standalone-outgoing"
                        ><ArrowLeft class="mr-2 size-4" />Kembali ke
                        konsep</Link
                    ></Button
                ><Badge
                    class="rounded-full bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200"
                    >{{ draft.status_label }}</Badge
                >
            </div>
            <header class="rounded-[2rem] border bg-card p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="max-w-3xl">
                        <p
                            class="text-sm font-medium text-violet-700 dark:text-violet-300"
                        >
                            {{ draft.unit.name }} ·
                            {{ draft.template.name }} v{{
                                draft.template.version_number
                            }}
                        </p>
                        <h1
                            class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            {{ draft.subject }}
                        </h1>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            Kepada
                            <span class="font-medium text-foreground">{{
                                draft.recipient.name
                            }}</span
                            >{{
                                draft.recipient.organization
                                    ? ` · ${draft.recipient.organization}`
                                    : ''
                            }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border bg-muted/30 px-4 py-3 text-xs text-muted-foreground"
                    >
                        <p>Disusun oleh {{ draft.created_by.name }}</p>
                        <p class="mt-1">
                            Terakhir diperbarui
                            {{ formatDate(draft.updated_at) }}
                        </p>
                    </div>
                </div>
            </header>

            <section
                class="relative grid gap-3 md:grid-cols-3"
                aria-label="Tahap pemeriksaan"
            >
                <div
                    class="absolute top-7 right-[16.6%] left-[16.6%] hidden h-px bg-border md:block"
                />
                <div
                    v-for="step in [
                        { label: 'Disiapkan staf', done: true },
                        {
                            label: 'Pemeriksaan Kabag',
                            done: [
                                'ASSISTANT_REVIEW',
                                'AWAITING_NUMBER',
                            ].includes(draft.status),
                            active: draft.status === 'SECTION_REVIEW',
                        },
                        {
                            label: 'Pemeriksaan Asisten',
                            done: draft.status === 'AWAITING_NUMBER',
                            active: draft.status === 'ASSISTANT_REVIEW',
                        },
                    ]"
                    :key="step.label"
                    class="relative rounded-2xl border bg-card p-4"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="grid size-7 place-items-center rounded-full text-xs font-bold"
                            :class="
                                step.done
                                    ? 'bg-emerald-600 text-white'
                                    : step.active
                                      ? 'bg-violet-600 text-white'
                                      : 'border bg-background text-muted-foreground'
                            "
                            ><Check v-if="step.done" class="size-4" /><Clock3
                                v-else
                                class="size-4" /></span
                        ><span class="text-sm font-semibold">{{
                            step.label
                        }}</span>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{
                            step.done
                                ? 'Selesai'
                                : step.active
                                  ? 'Sedang menunggu keputusan'
                                  : 'Menunggu tahap sebelumnya'
                        }}
                    </p>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="space-y-5">
                    <Card class="rounded-3xl"
                        ><CardContent class="p-5 sm:p-6"
                            ><h2 class="font-semibold">Dokumen konsep</h2>
                            <div class="mt-4 space-y-3">
                                <div
                                    v-for="version in draft.document_versions"
                                    :key="version.public_id"
                                    class="rounded-2xl border p-4"
                                >
                                    <div
                                        class="flex flex-wrap items-start justify-between gap-3"
                                    >
                                        <div>
                                            <p class="font-medium">
                                                Versi
                                                {{ version.version_number }} ·
                                                {{ version.original_filename }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ size(version.size_bytes) }} ·
                                                diunggah
                                                {{ version.uploaded_by.name }} ·
                                                {{
                                                    formatDate(
                                                        version.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <Button
                                                as-child
                                                size="sm"
                                                variant="outline"
                                                ><a
                                                    :href="
                                                        version.links.preview
                                                    "
                                                    target="_blank"
                                                    rel="noopener"
                                                    >Lihat</a
                                                ></Button
                                            ><Button
                                                as-child
                                                size="sm"
                                                variant="ghost"
                                                ><a
                                                    :href="
                                                        version.links.download
                                                    "
                                                    ><Download
                                                        class="size-4" /></a
                                            ></Button>
                                        </div>
                                    </div>
                                    <p
                                        v-if="version.revision_note"
                                        class="mt-3 rounded-xl bg-muted/40 p-3 text-sm text-muted-foreground"
                                    >
                                        Catatan versi:
                                        {{ version.revision_note }}
                                    </p>
                                    <p
                                        class="mt-3 font-mono text-[10px] break-all text-muted-foreground"
                                    >
                                        SHA-256 {{ version.sha256 }}
                                    </p>
                                </div>
                            </div></CardContent
                        ></Card
                    >
                    <Button
                        v-if="draft.can_edit && draft.actions.update"
                        variant="outline"
                        class="w-full rounded-2xl"
                        @click="editOpen = true"
                    >
                        Ubah informasi konsep
                    </Button>
                    <Dialog v-model:open="editOpen">
                        <DialogContent
                            class="max-h-[90vh] overflow-y-auto sm:max-w-2xl"
                        >
                            <DialogHeader>
                                <DialogTitle>Ubah informasi konsep</DialogTitle>
                                <DialogDescription>
                                    Informasi hanya dapat diubah sebelum dikirim
                                    atau setelah dikembalikan. PDF diperbarui
                                    dari panel dokumen.
                                </DialogDescription>
                            </DialogHeader>
                            <form
                                class="grid gap-4 py-2"
                                @submit.prevent="updateDraft"
                            >
                                <label class="grid gap-2"
                                    ><Label>Template</Label
                                    ><select
                                        v-model="editForm.template"
                                        class="h-10 rounded-md border bg-background px-3 text-sm"
                                    >
                                        <option
                                            v-for="template in templatesForDraftUnit"
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
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Nama penerima</Label
                                        ><Input
                                            v-model="editForm.recipientName"
                                            required /></label
                                    ><label class="grid gap-2"
                                        ><Label>Instansi penerima</Label
                                        ><Input
                                            v-model="
                                                editForm.recipientOrganization
                                            "
                                    /></label>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2"
                                        ><Label>Jabatan penerima</Label
                                        ><Input
                                            v-model="
                                                editForm.recipientPosition
                                            " /></label
                                    ><label class="grid gap-2"
                                        ><Label>Email penerima</Label
                                        ><Input
                                            v-model="editForm.recipientEmail"
                                            type="email"
                                    /></label>
                                </div>
                                <label class="grid gap-2"
                                    ><Label>Alamat</Label
                                    ><textarea
                                        v-model="editForm.recipientAddress"
                                        class="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                </label>
                                <label class="grid gap-2"
                                    ><Label>Perihal</Label
                                    ><Input v-model="editForm.subject" required
                                /></label>
                                <label class="grid gap-2"
                                    ><Label>Ringkasan</Label
                                    ><textarea
                                        v-model="editForm.summary"
                                        class="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                </label>
                                <label class="grid gap-2"
                                    ><Label>Tembusan</Label
                                    ><select
                                        v-model="editForm.copies"
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
                                        </option>
                                    </select></label
                                >
                                <div class="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="editOpen = false"
                                        >Batal</Button
                                    ><Button type="submit"
                                        >Simpan perubahan</Button
                                    >
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Card
                        v-if="
                            draft.can_edit &&
                            (draft.status === 'DRAFT' ||
                                draft.status === 'REVISION_REQUIRED')
                        "
                        class="rounded-3xl border-violet-200 bg-violet-50/40 dark:border-violet-900 dark:bg-violet-950/20"
                        ><CardContent class="p-5 sm:p-6"
                            ><h2 class="font-semibold">Perbarui PDF konsep</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Versi sebelumnya tetap tersimpan. Gunakan ini
                                setelah memperbaiki isi atau format surat.
                            </p>
                            <form
                                class="mt-4 grid gap-3"
                                @submit.prevent="uploadVersion"
                            >
                                <label class="grid gap-2"
                                    ><Label>PDF versi baru</Label
                                    ><Input
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        required
                                        @change="
                                            replacement =
                                                (
                                                    $event.target as HTMLInputElement
                                                ).files?.[0] ?? null
                                        " /></label
                                ><label class="grid gap-2"
                                    ><Label>Catatan perubahan</Label
                                    ><textarea
                                        v-model="replacementNote"
                                        class="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                        placeholder="Jelaskan perubahan penting pada versi ini."
                                    /></label
                                ><Button
                                    type="submit"
                                    variant="outline"
                                    class="w-fit"
                                    ><FileUp class="mr-2 size-4" />Simpan versi
                                    baru</Button
                                >
                            </form></CardContent
                        ></Card
                    >
                    <Card class="rounded-3xl"
                        ><CardContent class="p-5 sm:p-6"
                            ><h2 class="font-semibold">Riwayat pemeriksaan</h2>
                            <div
                                v-if="draft.reviews.length === 0"
                                class="mt-4 text-sm text-muted-foreground"
                            >
                                Belum ada keputusan pemeriksaan.
                            </div>
                            <ol v-else class="mt-4 space-y-4 border-l pl-5">
                                <li
                                    v-for="review in draft.reviews"
                                    :key="`${review.stage}-${review.created_at}`"
                                    class="relative"
                                >
                                    <span
                                        class="absolute top-1 -left-[1.6rem] grid size-3 place-items-center rounded-full bg-violet-600 ring-4 ring-background"
                                    />
                                    <p class="text-sm font-semibold">
                                        {{ review.stage_label }} ·
                                        {{ review.decision_label }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ review.decided_by.name }} ·
                                        {{ formatDate(review.created_at) }}
                                    </p>
                                    <p
                                        v-if="review.reason"
                                        class="mt-2 rounded-xl bg-muted/40 p-3 text-sm"
                                    >
                                        {{ review.reason }}
                                    </p>
                                </li>
                            </ol></CardContent
                        ></Card
                    >
                </div>
                <aside class="space-y-5">
                    <Card class="rounded-3xl"
                        ><CardContent class="p-5"
                            ><h2 class="font-semibold">Data penerima</h2>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">
                                        Nama
                                    </dt>
                                    <dd class="mt-1 font-medium">
                                        {{ draft.recipient.name }}
                                    </dd>
                                </div>
                                <div v-if="draft.recipient.position">
                                    <dt class="text-xs text-muted-foreground">
                                        Jabatan
                                    </dt>
                                    <dd class="mt-1">
                                        {{ draft.recipient.position }}
                                    </dd>
                                </div>
                                <div v-if="draft.recipient.address">
                                    <dt class="text-xs text-muted-foreground">
                                        Alamat
                                    </dt>
                                    <dd class="mt-1 whitespace-pre-line">
                                        {{ draft.recipient.address }}
                                    </dd>
                                </div>
                                <div v-if="draft.recipient.email">
                                    <dt class="text-xs text-muted-foreground">
                                        Email
                                    </dt>
                                    <dd class="mt-1 break-all">
                                        {{ draft.recipient.email }}
                                    </dd>
                                </div>
                            </dl>
                            <div
                                v-if="draft.copy_recipients.length"
                                class="mt-5 border-t pt-4"
                            >
                                <p
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    Tembusan
                                </p>
                                <ul class="mt-2 space-y-1 text-sm">
                                    <li
                                        v-for="copy in draft.copy_recipients"
                                        :key="copy.code"
                                    >
                                        {{ copy.name }}
                                    </li>
                                </ul>
                            </div></CardContent
                        ></Card
                    >
                    <Card
                        v-if="
                            draft.can_edit &&
                            draft.status !== 'SECTION_REVIEW' &&
                            draft.status !== 'ASSISTANT_REVIEW' &&
                            draft.status !== 'AWAITING_NUMBER'
                        "
                        class="rounded-3xl bg-violet-600 text-white"
                        ><CardContent class="p-5"
                            ><Send class="size-5" />
                            <h2 class="mt-3 font-semibold">Siap diperiksa?</h2>
                            <p class="mt-1 text-sm leading-6 text-violet-100">
                                Kirim konsep ini ke Kabag. Setelah dikirim, staf
                                tidak dapat mengubahnya sampai dikembalikan.
                            </p>
                            <Button
                                class="mt-4 w-full bg-white text-violet-700 hover:bg-violet-50"
                                @click="submitForReview"
                                >Kirim ke Kabag</Button
                            ></CardContent
                        ></Card
                    >
                    <Card
                        v-if="
                            draft.can_review_section ||
                            draft.can_review_assistant
                        "
                        class="rounded-3xl border-amber-200 bg-amber-50/50 dark:border-amber-900 dark:bg-amber-950/20"
                        ><CardContent class="p-5"
                            ><UserCheck
                                class="size-5 text-amber-700 dark:text-amber-300"
                            />
                            <h2 class="mt-3 font-semibold">Meja pemeriksaan</h2>
                            <p
                                class="mt-1 text-sm leading-6 text-muted-foreground"
                            >
                                Setujui untuk meneruskan proses atau kembalikan
                                dengan instruksi yang jelas.
                            </p>
                            <label class="mt-4 grid gap-2"
                                ><Label>Catatan bila dikembalikan</Label
                                ><textarea
                                    v-model="reviewReason"
                                    class="min-h-24 rounded-md border bg-background px-3 py-2 text-sm"
                                    placeholder="Minimal 10 karakter bila mengembalikan."
                                />
                            </label>
                            <div class="mt-4 grid gap-2">
                                <Button
                                    v-if="
                                        draft.can_review_section &&
                                        draft.status === 'SECTION_REVIEW'
                                    "
                                    @click="
                                        review(
                                            draft.actions.section_review,
                                            'APPROVED',
                                        )
                                    "
                                    ><Check class="mr-2 size-4" />Setujui ke
                                    Asisten</Button
                                ><Button
                                    v-if="
                                        draft.can_review_assistant &&
                                        draft.status === 'ASSISTANT_REVIEW'
                                    "
                                    @click="
                                        review(
                                            draft.actions.assistant_review,
                                            'APPROVED',
                                        )
                                    "
                                    ><Check class="mr-2 size-4" />Setujui, siap
                                    nomor</Button
                                ><Button
                                    v-if="
                                        (draft.can_review_section &&
                                            draft.status ===
                                                'SECTION_REVIEW') ||
                                        (draft.can_review_assistant &&
                                            draft.status === 'ASSISTANT_REVIEW')
                                    "
                                    variant="outline"
                                    class="border-rose-300 text-rose-700 hover:bg-rose-50"
                                    @click="
                                        review(
                                            draft.status === 'SECTION_REVIEW'
                                                ? draft.actions.section_review
                                                : draft.actions
                                                      .assistant_review,
                                            'RETURNED',
                                        )
                                    "
                                    ><RotateCcw class="mr-2 size-4" />Kembalikan
                                    ke staf</Button
                                >
                            </div></CardContent
                        ></Card
                    >
                </aside>
            </div>
        </section>
    </main>
</template>
