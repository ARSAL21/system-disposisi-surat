<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowRightLeft, Search, UserPlus, UserRoundX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
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
    ActivePositionAssignment,
    AssignmentUser,
    OrganizationPosition,
} from '@/types';

const props = defineProps<{
    open: boolean;
    position: OrganizationPosition | null;
    users: AssignmentUser[];
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    end: [assignment: ActivePositionAssignment];
}>();
const search = ref('');
const form = useForm<{ user_id: number | null }>({ user_id: null });
const availableUsers = computed(() => {
    const term = search.value.trim().toLocaleLowerCase('id-ID');

    return props.users.filter(
        (user) =>
            user.id !== props.position?.active_assignment?.user.id &&
            (!term ||
                `${user.name} ${user.email}`
                    .toLocaleLowerCase('id-ID')
                    .includes(term)),
    );
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            search.value = '';
            form.reset();
            form.clearErrors();
        }
    },
);
function submit(): void {
    if (!props.position) {
        return;
    }

    const url = props.position.active_assignment
        ? props.position.links.replace
        : props.position.links.assign;
    form.post(url, {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    });
}
function requestEnd(): void {
    if (!props.position?.active_assignment) {
        return;
    }

    emit('end', props.position.active_assignment);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader
                ><DialogTitle>{{
                    position?.active_assignment
                        ? 'Ganti pejabat aktif'
                        : 'Tugaskan pejabat'
                }}</DialogTitle
                ><DialogDescription
                    >{{ position?.name }} · perubahan efektif memakai waktu
                    server dan seluruh histori dipertahankan.</DialogDescription
                ></DialogHeader
            >
            <div
                v-if="position?.active_assignment"
                class="flex items-center justify-between gap-3 rounded-xl border bg-muted/40 p-3"
            >
                <div>
                    <p class="text-xs text-muted-foreground">
                        Pejabat saat ini
                    </p>
                    <p class="mt-1 font-medium">
                        {{ position.active_assignment.user.name }}
                    </p>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    class="min-h-10 text-destructive"
                    @click="requestEnd"
                    ><UserRoundX class="size-4" aria-hidden="true" /> Akhiri
                    masa jabatan</Button
                >
            </div>
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="assignment-search">Cari akun internal</Label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        /><Input
                            id="assignment-search"
                            v-model="search"
                            class="pl-9"
                            placeholder="Nama atau email"
                        />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="assignment-user">Pejabat baru</Label
                    ><select
                        id="assignment-user"
                        v-model="form.user_id"
                        class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                        required
                    >
                        <option :value="null" disabled>
                            Pilih akun internal aktif
                        </option>
                        <option
                            v-for="user in availableUsers"
                            :key="user.id"
                            :value="user.id"
                        >
                            {{ user.name }} — {{ user.email }}
                        </option>
                    </select>
                    <p
                        v-if="!availableUsers.length"
                        class="text-sm text-muted-foreground"
                    >
                        Tidak ada akun yang cocok dengan pencarian.
                    </p>
                    <InputError :message="form.errors.user_id" />
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
                        class="min-h-11 bg-violet-700 hover:bg-violet-800"
                        :disabled="form.processing || !form.user_id"
                        ><Spinner v-if="form.processing" /><ArrowRightLeft
                            v-else-if="position?.active_assignment"
                            class="size-4"
                            aria-hidden="true"
                        /><UserPlus
                            v-else
                            class="size-4"
                            aria-hidden="true"
                        />{{
                            position?.active_assignment
                                ? 'Ganti pejabat'
                                : 'Tugaskan pejabat'
                        }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
