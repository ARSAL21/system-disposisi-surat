<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { UserRoundX } from '@lucide/vue';
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
import type { ActivePositionAssignment } from '@/types';

const props = defineProps<{
    open: boolean;
    assignment: ActivePositionAssignment | null;
}>();
const emit = defineEmits<{ 'update:open': [open: boolean] }>();
const form = useForm<Record<string, never>>({});
function submit(): void {
    if (!props.assignment) {
        return;
    }

    form.patch(props.assignment.links.end, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)"
        ><DialogContent class="sm:max-w-md"
            ><DialogHeader
                ><DialogTitle>Akhiri masa jabatan?</DialogTitle
                ><DialogDescription
                    >{{ assignment?.user.name }} akan dilepas dari jabatan ini
                    menggunakan waktu efektif server. Catatan histori tidak
                    dapat diedit atau dihapus.</DialogDescription
                ></DialogHeader
            ><DialogFooter
                ><Button
                    variant="outline"
                    class="min-h-11"
                    @click="emit('update:open', false)"
                    >Batal</Button
                ><Button
                    variant="destructive"
                    class="min-h-11"
                    :disabled="form.processing"
                    @click="submit"
                    ><Spinner v-if="form.processing" /><UserRoundX
                        v-else
                        class="size-4"
                        aria-hidden="true"
                    />
                    Akhiri masa jabatan</Button
                ></DialogFooter
            ></DialogContent
        ></Dialog
    >
</template>
