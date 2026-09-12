<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CalendarDays, FileText, Hash, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import type { StandaloneNumberQueueItem } from '@/types';

const props = withDefaults(
    defineProps<{ items: StandaloneNumberQueueItem[]; preview?: boolean }>(),
    { preview: false },
);
const activeItem = ref<StandaloneNumberQueueItem | null>(null);
const open = ref(false);
const previewCompletedIds = ref<string[]>([]);
const form = useForm({ outgoing_number: '', letter_date: '' });

const visibleItems = computed(() =>
    props.items.filter(
        (item) => !previewCompletedIds.value.includes(item.public_id),
    ),
);

function begin(item: StandaloneNumberQueueItem): void {
    activeItem.value = item;
    form.reset();
    form.clearErrors();
    open.value = true;
}

function submit(): void {
    if (!activeItem.value?.assign_number_url) {
        return;
    }

    if (props.preview) {
        previewCompletedIds.value = [
            ...previewCompletedIds.value,
            activeItem.value.public_id,
        ];
        open.value = false;
        toast.success(
            'Simulasi: nomor surat dicatat dan masuk antrean pengesahan Sekda.',
        );

        return;
    }

    form.post(activeItem.value.assign_number_url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            toast.success(
                'Nomor surat berhasil dicatat. Surat kini menunggu pengesahan Sekda.',
            );
        },
    });
}
</script>

<template>
    <section
        v-if="visibleItems.length"
        class="overflow-hidden rounded-3xl border border-indigo-200 bg-indigo-50/35 shadow-sm dark:border-indigo-950 dark:bg-indigo-950/15"
        aria-labelledby="standalone-numbering-heading"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-4 border-b border-indigo-200/70 bg-background/60 px-5 py-4 sm:px-6 dark:border-indigo-950"
        >
            <div class="flex items-center gap-3">
                <span
                    class="grid size-10 place-items-center rounded-2xl bg-indigo-600 text-white"
                    ><Hash class="size-5"
                /></span>
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-indigo-700 uppercase dark:text-indigo-300"
                    >
                        Tindakan Petugas
                    </p>
                    <h2
                        id="standalone-numbering-heading"
                        class="mt-0.5 text-lg font-semibold"
                    >
                        Menunggu nomor surat
                    </h2>
                </div>
            </div>
            <Badge class="rounded-full bg-indigo-600"
                >{{ visibleItems.length }} surat mandiri</Badge
            >
        </header>

        <div class="grid divide-y divide-indigo-200/70 dark:divide-indigo-950">
            <article
                v-for="item in visibleItems"
                :key="item.public_id"
                class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="rounded-full"
                            >Surat keluar mandiri</Badge
                        ><Badge variant="outline" class="rounded-full">{{
                            item.originating_unit_name
                        }}</Badge>
                    </div>
                    <h3 class="mt-3 text-base leading-snug font-semibold">
                        {{ item.subject }}
                    </h3>
                    <div
                        class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-1.5"
                            ><FileText class="size-3.5" />Kepada
                            {{ item.recipient_name }}</span
                        ><span class="inline-flex items-center gap-1.5"
                            ><ShieldCheck class="size-3.5" />PDF konsep v{{
                                item.current_version_number
                            }}
                            telah disetujui Asisten</span
                        >
                    </div>
                </div>
                <Button
                    type="button"
                    class="min-h-11 rounded-xl lg:min-w-48"
                    :disabled="!item.assign_number_url"
                    @click="begin(item)"
                    ><Hash class="size-4" /> Beri nomor surat</Button
                >
            </article>
        </div>
    </section>

    <Dialog
        :open="open"
        @update:open="!form.processing ? (open = $event) : undefined"
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <span
                    class="mb-2 grid size-11 place-items-center rounded-2xl bg-indigo-600 text-white"
                    ><Hash class="size-5"
                /></span>
                <DialogTitle>Catat nomor surat resmi</DialogTitle>
                <DialogDescription
                    >Nomor berlaku satu kali dalam tahun agenda. Setelah
                    disimpan, nomor tidak dapat digunakan
                    ulang.</DialogDescription
                >
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submit">
                <div
                    v-if="activeItem"
                    class="rounded-2xl border bg-muted/25 p-4 text-sm"
                >
                    <p class="font-semibold">{{ activeItem.subject }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ activeItem.originating_unit_name }} · kepada
                        {{ activeItem.recipient_name }}
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="standalone-outgoing-number">Nomor surat</Label
                    ><Input
                        id="standalone-outgoing-number"
                        v-model="form.outgoing_number"
                        required
                        minlength="3"
                        maxlength="100"
                        pattern="[\p{L}\p{N} .\-/]+"
                        placeholder="Contoh: 005/1201/SETDA/2026"
                    /><InputError :message="form.errors.outgoing_number" />
                </div>
                <div class="space-y-2">
                    <Label for="standalone-letter-date"
                        ><CalendarDays class="mr-1 inline size-3.5" />Tanggal
                        surat</Label
                    ><Input
                        id="standalone-letter-date"
                        v-model="form.letter_date"
                        required
                        type="date"
                    /><InputError :message="form.errors.letter_date" />
                </div>
                <DialogFooter
                    ><Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="open = false"
                        >Batal</Button
                    ><Button type="submit" :disabled="form.processing"
                        ><Spinner v-if="form.processing" />Simpan dan ajukan ke
                        Sekda</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
