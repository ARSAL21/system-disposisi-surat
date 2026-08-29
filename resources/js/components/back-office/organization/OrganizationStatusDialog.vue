<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Power } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    open: boolean;
    name: string;
    isActive: boolean;
    url: string;
}>();
const emit = defineEmits<{ 'update:open': [open: boolean] }>();
const form = useForm<{ is_active: boolean }>({ is_active: false });
function submit(): void {
    form.is_active = !props.isActive;
    form.patch(props.url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader
                ><DialogTitle
                    >{{ isActive ? 'Nonaktifkan' : 'Aktifkan' }}
                    {{ name }}?</DialogTitle
                ><DialogDescription v-if="isActive"
                    >Server akan menolak jika resource masih memiliki dependency
                    aktif. Data histori tidak akan dihapus.</DialogDescription
                ><DialogDescription v-else
                    >Resource hanya dapat diaktifkan bila dependency induknya
                    masih aktif.</DialogDescription
                ></DialogHeader
            >
            <DialogFooter
                ><Button
                    variant="outline"
                    class="min-h-11"
                    @click="emit('update:open', false)"
                    >Batal</Button
                ><Button
                    class="min-h-11"
                    :variant="isActive ? 'destructive' : 'default'"
                    :disabled="form.processing"
                    @click="submit"
                    ><Spinner v-if="form.processing" /><Power
                        v-else
                        class="size-4"
                        aria-hidden="true"
                    />{{ isActive ? 'Nonaktifkan' : 'Aktifkan' }}</Button
                ></DialogFooter
            >
        </DialogContent>
    </Dialog>
</template>
