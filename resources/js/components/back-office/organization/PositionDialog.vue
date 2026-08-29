<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { BriefcaseBusiness } from '@lucide/vue';
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
import type {
    OrganizationPosition,
    OrganizationalUnitOption,
    PositionLevel,
} from '@/types';

const props = defineProps<{
    open: boolean;
    position: OrganizationPosition | null;
    levels: PositionLevel[];
    units: OrganizationalUnitOption[];
    storeUrl: string;
}>();
const emit = defineEmits<{ 'update:open': [open: boolean] }>();
const form = useForm<{
    code: string;
    name: string;
    position_level_id: number | null;
    organizational_unit_id: number | null;
}>({
    code: '',
    name: '',
    position_level_id: null,
    organizational_unit_id: null,
});

watch(
    () => [props.open, props.position] as const,
    () => {
        if (!props.open) {
            return;
        }

        form.clearErrors();
        form.code = props.position?.code ?? '';
        form.name = props.position?.name ?? '';
        form.position_level_id =
            props.position?.position_level_id ?? props.levels[0]?.id ?? null;
        form.organizational_unit_id =
            props.position?.organizational_unit_id ?? null;
    },
    { immediate: true },
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (props.position) {
        form.patch(props.position.links.update, options);
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
                    position ? 'Ubah jabatan' : 'Buat jabatan'
                }}</DialogTitle
                ><DialogDescription
                    >Kode dan level tidak dapat diubah setelah jabatan dibuat
                    untuk menjaga makna histori.</DialogDescription
                ></DialogHeader
            >
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="position-name">Nama jabatan</Label
                    ><Input
                        id="position-name"
                        v-model="form.name"
                        required
                        maxlength="150"
                    /><InputError :message="form.errors.name" />
                </div>
                <div class="space-y-2">
                    <Label for="position-code">Kode jabatan</Label
                    ><Input
                        id="position-code"
                        v-model="form.code"
                        :disabled="Boolean(position)"
                        required
                        maxlength="80"
                        placeholder="KABAG_UMUM"
                        class="font-mono uppercase"
                    /><InputError :message="form.errors.code" />
                </div>
                <div class="space-y-2">
                    <Label for="position-level">Level workflow</Label
                    ><select
                        id="position-level"
                        v-model="form.position_level_id"
                        :disabled="Boolean(position)"
                        class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                        required
                    >
                        <option
                            v-for="level in levels"
                            :key="level.id"
                            :value="level.id"
                        >
                            {{ level.name }}
                        </option></select
                    ><InputError :message="form.errors.position_level_id" />
                </div>
                <div class="space-y-2">
                    <Label for="position-unit">Unit organisasi</Label
                    ><select
                        id="position-unit"
                        v-model="form.organizational_unit_id"
                        class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                    >
                        <option :value="null">
                            Lintas unit / tidak terikat
                        </option>
                        <option
                            v-for="unit in units"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.name }}
                        </option></select
                    ><InputError
                        :message="form.errors.organizational_unit_id"
                    />
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
                        ><Spinner v-if="form.processing" /><BriefcaseBusiness
                            v-else
                            class="size-4"
                            aria-hidden="true"
                        />{{
                            position ? 'Simpan perubahan' : 'Buat jabatan'
                        }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
