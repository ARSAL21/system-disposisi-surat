<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { AuthorizationRole } from '@/types';

const props = defineProps<{ open: boolean; role: AuthorizationRole | null }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
const form = useForm<{ name: string }>({ name: '' });

watch(
    () => [props.open, props.role] as const,
    ([open, role]) => {
        if (!open || !role) {
            return;
        }

        form.name = role.name;
        form.clearErrors();
    },
);

function submit(): void {
    if (!props.role) {
        return;
    }

    form.patch(props.role.links.update, {
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
                    <DialogTitle>Ubah nama role</DialogTitle>
                    <DialogDescription class="leading-6">
                        Assignment dan permission tetap dipertahankan. Perubahan
                        nama akan dicatat pada audit privilege.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="rename-role-name">Nama role</Label>
                    <Input
                        id="rename-role-name"
                        v-model="form.name"
                        class="h-11"
                        autocomplete="off"
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11"
                        >
                            Batal
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        class="min-h-11"
                        :disabled="form.processing"
                    >
                        <Spinner
                            v-if="form.processing"
                            class="size-4 animate-spin motion-reduce:animate-none"
                            aria-hidden="true"
                        />
                        <Pencil v-else class="size-4" aria-hidden="true" />
                        Simpan perubahan
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
