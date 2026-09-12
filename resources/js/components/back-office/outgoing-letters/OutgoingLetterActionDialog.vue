<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    BadgeCheck,
    FilePenLine,
    FileUp,
    Hash,
    RotateCcw,
    Send,
    ShieldX,
} from '@lucide/vue';
import { computed, watch } from 'vue';
import type { Component } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { OutgoingDeliveryMethod, OutgoingLetterUiAction } from '@/types';

const props = defineProps<{
    open: boolean;
    action: OutgoingLetterUiAction | null;
    preview?: boolean;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    completed: [
        action: OutgoingLetterUiAction['kind'],
        payload: Record<string, string | File | null>,
    ];
}>();

const form = useForm({
    outgoing_number: '',
    letter_date: '',
    signed_document: null as File | null,
    upload_note: '',
    verification_note: '',
    revision_reason: '',
    delivery_method: 'IN_PERSON' as OutgoingDeliveryMethod,
    recipient_name: '',
    delivered_at: '',
    tracking_number: '',
    delivery_note: '',
    withdrawal_reason: '',
    correction_reason: '',
});

const presentation = computed<{
    title: string;
    description: string;
    submit: string;
    icon: Component;
}>(() => {
    switch (props.action?.kind) {
        case 'assign_number':
            return {
                title: 'Berikan nomor resmi',
                description:
                    'Nomor harus unik pada tahun agenda dan tidak dapat diganti setelah dokumen final diunggah.',
                submit: 'Simpan nomor',
                icon: Hash,
            };
        case 'upload_signed_document':
            return {
                title: 'Unggah PDF bertanda tangan',
                description:
                    'Gunakan PDF final yang sudah memuat nomor resmi dan tanda tangan di luar sistem.',
                submit: 'Unggah dokumen',
                icon: FileUp,
            };
        case 'verify':
            return {
                title: 'Verifikasi administrasi',
                description:
                    'Pastikan nomor, tanggal, penandatangan, dan isi PDF sesuai mandat sebelum disetujui.',
                submit: 'Nyatakan sesuai',
                icon: BadgeCheck,
            };
        case 'request_document_revision':
            return {
                title: 'Minta perbaikan dokumen',
                description:
                    'Versi lama tetap tersimpan. Pengunggah harus mengirim versi PDF baru sebelum diverifikasi.',
                submit: 'Kirim catatan',
                icon: RotateCcw,
            };
        case 'deliver':
            return {
                title:
                    props.action.source === 'ONLINE'
                        ? 'Publikasikan kepada pemohon'
                        : 'Catat penyerahan surat',
                description:
                    props.action.source === 'ONLINE'
                        ? 'Balasan akan tersedia pada portal pemohon setelah konfirmasi.'
                        : 'Simpan penerima dan metode penyerahan sebagai bukti administratif.',
                submit: 'Konfirmasi pengiriman',
                icon: Send,
            };
        case 'create_correction':
            return {
                title: 'Buat surat koreksi',
                description:
                    'Surat lama tetap tersimpan. Sistem akan membuat konsep baru dengan nomor surat baru dan menyimpan hubungan koreksinya.',
                submit: 'Mulai surat koreksi',
                icon: FilePenLine,
            };
        default:
            return {
                title: 'Tarik mandat',
                description:
                    'Hanya mandat yang belum diberi nomor yang dapat ditarik. Riwayat dan alasannya tetap tersimpan.',
                submit: 'Tarik mandat',
                icon: ShieldX,
            };
    }
});

watch(
    () => [props.open, props.action] as const,
    () => {
        if (!props.open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function chooseDocument(event: Event): void {
    form.signed_document =
        (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    if (!props.action) {
        return;
    }

    if (props.preview) {
        emit('completed', props.action.kind, { ...form.data() });
        emit('update:open', false);

        return;
    }

    form.post(props.action.route, {
        forceFormData: props.action.kind === 'upload_signed_document',
        preserveScroll: true,
        onSuccess: () => {
            emit('completed', props.action!.kind, { ...form.data() });
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            !form.processing ? emit('update:open', $event) : undefined
        "
    >
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <span
                    class="mb-2 grid size-11 place-items-center rounded-2xl bg-indigo-600 text-white"
                    ><component :is="presentation.icon" class="size-5"
                /></span>
                <DialogTitle>{{ presentation.title }}</DialogTitle>
                <DialogDescription class="leading-6">{{
                    presentation.description
                }}</DialogDescription>
            </DialogHeader>

            <form v-if="action" class="space-y-4" @submit.prevent="submit">
                <template v-if="action.kind === 'assign_number'">
                    <div class="space-y-2">
                        <Label for="outgoing-number">Nomor surat resmi</Label
                        ><Input
                            id="outgoing-number"
                            v-model="form.outgoing_number"
                            required
                            minlength="3"
                            maxlength="100"
                            placeholder="Contoh: 005/1193/SETDA/2026"
                        /><InputError :message="form.errors.outgoing_number" />
                    </div>
                    <div class="space-y-2">
                        <Label for="outgoing-letter-date">Tanggal surat</Label
                        ><Input
                            id="outgoing-letter-date"
                            v-model="form.letter_date"
                            required
                            type="date"
                        /><InputError :message="form.errors.letter_date" />
                    </div>
                </template>

                <template v-else-if="action.kind === 'upload_signed_document'">
                    <div class="space-y-2">
                        <Label for="signed-document">PDF final</Label
                        ><Input
                            id="signed-document"
                            required
                            type="file"
                            accept="application/pdf,.pdf"
                            @change="chooseDocument"
                        />
                        <p class="text-xs text-muted-foreground">
                            PDF maksimal 20 MB. Versi sebelumnya tidak akan
                            ditimpa.
                        </p>
                        <InputError :message="form.errors.signed_document" />
                    </div>
                    <div class="space-y-2">
                        <Label for="signed-upload-note">Catatan unggahan</Label
                        ><textarea
                            id="signed-upload-note"
                            v-model="form.upload_note"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="4"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Jelaskan sumber dan kondisi dokumen final."
                        /><InputError :message="form.errors.upload_note" />
                    </div>
                </template>

                <template v-else-if="action.kind === 'verify'">
                    <div
                        class="grid gap-2 rounded-2xl border bg-muted/25 p-4 text-sm"
                    >
                        <p class="font-semibold">Konfirmasi pemeriksaan</p>
                        <p>✓ Nomor dan tanggal surat terbaca jelas</p>
                        <p>✓ Penandatangan sesuai mandat eksekutif</p>
                        <p>✓ PDF merupakan dokumen final lengkap</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="verification-note"
                            >Catatan verifikasi (opsional)</Label
                        ><textarea
                            id="verification-note"
                            v-model="form.verification_note"
                            maxlength="2000"
                            rows="3"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        /><InputError
                            :message="form.errors.verification_note"
                        />
                    </div>
                </template>

                <template
                    v-else-if="action.kind === 'request_document_revision'"
                >
                    <div class="space-y-2">
                        <Label for="revision-reason"
                            >Bagian yang harus diperbaiki</Label
                        ><textarea
                            id="revision-reason"
                            v-model="form.revision_reason"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="5"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Jelaskan ketidaksesuaian secara spesifik."
                        /><InputError :message="form.errors.revision_reason" />
                    </div>
                </template>

                <template v-else-if="action.kind === 'deliver'">
                    <div
                        v-if="action.source === 'ONLINE'"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100"
                    >
                        Pemohon akan menerima notifikasi tanpa lampiran. PDF
                        hanya dapat diunduh setelah login ke portal.
                    </div>
                    <template v-else>
                        <div class="space-y-2">
                            <Label for="delivery-method"
                                >Metode penyerahan</Label
                            ><select
                                id="delivery-method"
                                v-model="form.delivery_method"
                                required
                                class="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm"
                            >
                                <option value="IN_PERSON">
                                    Diserahkan langsung
                                </option>
                                <option value="POSTAL">Pos tercatat</option>
                                <option value="COURIER">Kurir</option>
                                <option value="OTHER">Metode lainnya</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="recipient-name">Nama penerima</Label
                            ><Input
                                id="recipient-name"
                                v-model="form.recipient_name"
                                required
                                minlength="3"
                                maxlength="150"
                            /><InputError
                                :message="form.errors.recipient_name"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="delivered-at">Waktu penyerahan</Label
                            ><Input
                                id="delivered-at"
                                v-model="form.delivered_at"
                                required
                                type="datetime-local"
                            /><InputError :message="form.errors.delivered_at" />
                        </div>
                        <div class="space-y-2">
                            <Label for="tracking-number"
                                >Nomor resi/referensi (opsional)</Label
                            ><Input
                                id="tracking-number"
                                v-model="form.tracking_number"
                                maxlength="100"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="delivery-note"
                                >Catatan penyerahan (opsional)</Label
                            ><textarea
                                id="delivery-note"
                                v-model="form.delivery_note"
                                maxlength="2000"
                                rows="3"
                                class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </div>
                    </template>
                </template>

                <template v-else-if="action.kind === 'create_correction'">
                    <div
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100"
                    >
                        Nomor, PDF, dan bukti pengiriman surat lama tidak akan
                        diubah atau dihapus.
                    </div>
                    <div class="space-y-2">
                        <Label for="correction-reason">Alasan koreksi</Label
                        ><textarea
                            id="correction-reason"
                            v-model="form.correction_reason"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="5"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Jelaskan bagian yang perlu dikoreksi."
                        /><InputError
                            :message="form.errors.correction_reason"
                        />
                    </div>
                </template>

                <template v-else>
                    <div class="space-y-2">
                        <Label for="withdrawal-reason">Alasan penarikan</Label
                        ><textarea
                            id="withdrawal-reason"
                            v-model="form.withdrawal_reason"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="5"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        /><InputError
                            :message="form.errors.withdrawal_reason"
                        />
                    </div>
                </template>

                <DialogFooter class="gap-2 sm:gap-0"
                    ><Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="emit('update:open', false)"
                        >Batal</Button
                    ><Button
                        type="submit"
                        :variant="
                            action.kind === 'withdraw'
                                ? 'destructive'
                                : 'default'
                        "
                        :disabled="form.processing"
                        ><Spinner v-if="form.processing" />{{
                            presentation.submit
                        }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
