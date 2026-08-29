<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
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
import type { OrganizationalUnit, OrganizationalUnitOption } from '@/types';

const props = defineProps<{
    open: boolean;
    unit: OrganizationalUnit | null;
    options: OrganizationalUnitOption[];
    storeUrl: string;
}>();
const emit = defineEmits<{ 'update:open': [open: boolean] }>();
const form = useForm<{ code: string; name: string; parent_id: number | null }>({
    code: '',
    name: '',
    parent_id: null,
});

watch(
    () => [props.open, props.unit] as const,
    () => {
        if (!props.open) {
            return;
        }

        form.clearErrors();
        form.code = props.unit?.code ?? '';
        form.name = props.unit?.name ?? '';
        form.parent_id = props.unit?.parent_id ?? null;
    },
    { immediate: true },
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (props.unit) {
        form.patch(props.unit.links.update, options);
    } else {
        form.post(props.storeUrl, options);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader
                ><DialogTitle>{{
                    unit ? 'Ubah unit organisasi' : 'Buat unit organisasi'
                }}</DialogTitle
                ><DialogDescription
                    >Kode bersifat identitas tetap. Relasi induk diverifikasi
                    server untuk mencegah siklus.</DialogDescription
                ></DialogHeader
            >
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="unit-name">Nama unit</Label
                    ><Input
                        id="unit-name"
                        v-model="form.name"
                        required
                        maxlength="150"
                        autocomplete="off"
                    /><InputError :message="form.errors.name" />
                </div>
                <div class="space-y-2">
                    <Label for="unit-code">Kode unit</Label
                    ><Input
                        id="unit-code"
                        v-model="form.code"
                        :disabled="Boolean(unit)"
                        maxlength="50"
                        placeholder="BAGIAN_UMUM"
                        class="font-mono uppercase"
                    />
                    <p class="text-xs text-muted-foreground">
                        Opsional; gunakan huruf kapital, angka, _ atau -.
                    </p>
                    <InputError :message="form.errors.code" />
                </div>
                <div class="space-y-2">
                    <Label for="unit-parent">Unit induk</Label
                    ><select
                        id="unit-parent"
                        v-model="form.parent_id"
                        class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                    >
                        <option :value="null">Tidak ada (root)</option>
                        <option
                            v-for="option in options.filter(
                                (item) => item.id !== unit?.id,
                            )"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </option></select
                    ><InputError :message="form.errors.parent_id" />
                </div>
                <DialogFooter
                    ><Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        @click="emit('update:open', false)"
                        >Batal</Button
                    ><Button
                        type="submit"
                        class="min-h-11 bg-blue-700 hover:bg-blue-800"
                        :disabled="form.processing"
                        ><Spinner v-if="form.processing" /><Building2
                            v-else
                            class="size-4"
                            aria-hidden="true"
                        />{{ unit ? 'Simpan perubahan' : 'Buat unit' }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
