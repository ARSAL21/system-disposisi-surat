<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ListChecks } from '@lucide/vue';
import { watch } from 'vue';
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
import type { DispositionInstructionLabel } from '@/types';

const props = defineProps<{
    open: boolean;
    label: DispositionInstructionLabel | null;
    storeUrl?: string;
    preview?: boolean;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    'preview:save': [
        payload: {
            label: DispositionInstructionLabel | null;
            code: string;
            name: string;
            description: string;
            sort_order: number;
        },
    ];
}>();

const form = useForm({
    code: '',
    name: '',
    description: '',
    sort_order: 10,
});

watch(
    () => [props.open, props.label] as const,
    () => {
        if (!props.open) {
            return;
        }

        form.clearErrors();
        form.code = props.label?.code ?? '';
        form.name = props.label?.name ?? '';
        form.description = props.label?.description ?? '';
        form.sort_order = props.label?.sort_order ?? 10;
    },
    { immediate: true },
);

function submit(): void {
    if (props.preview) {
        form.clearErrors();

        if (!form.name.trim()) {
            form.setError('name', 'Nama instruksi wajib diisi.');
        }

        if (!form.code.trim()) {
            form.setError('code', 'Kode instruksi wajib diisi.');
        }

        if (form.hasErrors) {
            return;
        }

        emit('preview:save', {
            label: props.label,
            code:
                props.label?.code ??
                form.code.trim().toLocaleUpperCase('id-ID'),
            name: form.name.trim(),
            description: form.description.trim(),
            sort_order: Number(form.sort_order),
        });
        emit('update:open', false);

        return;
    }

    if (!props.storeUrl && !props.label) {
        form.setError('name', 'Endpoint penyimpanan belum tersedia.');

        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (props.label) {
        form.patch(props.label.links.update, options);
    } else if (props.storeUrl) {
        form.post(props.storeUrl, options);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ label ? 'Ubah instruksi' : 'Tambah instruksi' }}
                </DialogTitle>
                <DialogDescription class="leading-6">
                    Label aktif akan tersedia pada form disposisi berikutnya.
                    Label yang sudah digunakan tetap terhubung ke histori dan
                    tidak dapat dihapus.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="instruction-name">
                        Nama instruksi <span aria-hidden="true">*</span>
                    </Label>
                    <Input
                        id="instruction-name"
                        v-model="form.name"
                        required
                        maxlength="120"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="instruction-code">
                        Kode <span aria-hidden="true">*</span>
                    </Label>
                    <Input
                        id="instruction-code"
                        v-model="form.code"
                        required
                        :disabled="Boolean(label)"
                        maxlength="80"
                        autocomplete="off"
                        class="font-mono uppercase"
                        placeholder="UNTUK_DITINDAKLANJUTI"
                    />
                    <p class="text-xs leading-5 text-muted-foreground">
                        <template v-if="label">
                            Kode adalah identitas tetap dan tidak dapat diubah.
                        </template>
                        <template v-else>
                            Gunakan huruf kapital, angka, atau garis bawah.
                        </template>
                    </p>
                    <InputError :message="form.errors.code" />
                </div>

                <div class="space-y-2">
                    <Label for="instruction-description">Deskripsi</Label>
                    <textarea
                        id="instruction-description"
                        v-model="form.description"
                        rows="3"
                        maxlength="500"
                        class="min-h-24 w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="space-y-2">
                    <Label for="instruction-sort-order">Urutan tampil</Label>
                    <Input
                        id="instruction-sort-order"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                        max="9999"
                        class="min-h-11"
                    />
                    <InputError :message="form.errors.sort_order" />
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        :disabled="form.processing"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-11 bg-blue-700 hover:bg-blue-800"
                        :disabled="form.processing"
                    >
                        <Spinner v-if="form.processing" />
                        <ListChecks v-else class="size-4" aria-hidden="true" />
                        {{ label ? 'Simpan perubahan' : 'Tambah instruksi' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
