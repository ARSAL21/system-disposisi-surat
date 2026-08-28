<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
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

const props = defineProps<{ open: boolean; storeUrl: string }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
const form = useForm<{ name: string }>({ name: '' });

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.reset();
            form.clearErrors();
        }
    },
);

function submit(): void {
    form.post(props.storeUrl, {
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
                    <DialogTitle>Buat custom role</DialogTitle>
                    <DialogDescription class="leading-6">
                        Buat identitas role terlebih dahulu. Permission dapat
                        disusun setelah role tersimpan.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2">
                    <Label for="new-role-name">Nama role</Label>
                    <Input
                        id="new-role-name"
                        v-model="form.name"
                        class="h-11"
                        placeholder="contoh: pengelola-surat"
                        autocomplete="off"
                        autofocus
                        :aria-invalid="Boolean(form.errors.name)"
                    />
                    <p class="text-sm leading-6 text-muted-foreground">
                        Gunakan slug lowercase dan tanda hubung. Nama protected
                        tidak dapat digunakan.
                    </p>
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
                        <Plus v-else class="size-4" aria-hidden="true" />
                        Simpan role
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
