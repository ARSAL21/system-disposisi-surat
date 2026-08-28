<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Trash2, TriangleAlert } from '@lucide/vue';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { AuthorizationRole } from '@/types';

const props = defineProps<{ open: boolean; role: AuthorizationRole | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
const form = useForm({});

watch(
    () => props.open,
    (open) => open && form.clearErrors(),
);

function submit(): void {
    if (!props.role) {
        return;
    }

    form.delete(props.role.links.delete, {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogContent class="rounded-2xl sm:max-w-lg">
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <span
                        class="mb-2 flex size-11 items-center justify-center rounded-2xl bg-destructive/10 text-destructive"
                    >
                        <TriangleAlert class="size-5" aria-hidden="true" />
                    </span>
                    <DialogTitle>Hapus role {{ role?.name }}?</DialogTitle>
                    <DialogDescription class="leading-6">
                        Role akan dihapus permanen. Audit perubahan tetap
                        dipertahankan dan tindakan ini hanya dapat dilakukan
                        bila role tidak lagi digunakan.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11"
                        >
                            Batalkan
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        class="min-h-11"
                        :disabled="form.processing"
                    >
                        <Spinner
                            v-if="form.processing"
                            class="size-4 animate-spin motion-reduce:animate-none"
                            aria-hidden="true"
                        />
                        <Trash2 v-else class="size-4" aria-hidden="true" />
                        Hapus role
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
