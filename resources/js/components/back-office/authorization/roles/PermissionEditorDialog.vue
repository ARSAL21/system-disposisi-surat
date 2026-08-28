<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound, Save } from '@lucide/vue';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { AuthorizationPermission, AuthorizationRole } from '@/types';

const props = defineProps<{
    open: boolean;
    role: AuthorizationRole | null;
    permissions: AuthorizationPermission[];
}>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
const form = useForm<{ permissions: string[] }>({ permissions: [] });

watch(
    () => [props.open, props.role] as const,
    ([open, role]) => {
        if (!open || !role) {
            return;
        }

        form.permissions = [...role.permissions];
        form.clearErrors();
    },
);

function togglePermission(permission: string, checked: boolean): void {
    form.permissions = checked
        ? [...new Set([...form.permissions, permission])]
        : form.permissions.filter((name) => name !== permission);
}

function submit(): void {
    if (!props.role) {
        return;
    }

    form.put(props.role.links.permissions, {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent class="sm:max-w-2xl">
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <span
                        class="mb-2 flex size-11 items-center justify-center rounded-2xl bg-blue-600/10 text-blue-700 dark:text-blue-300"
                    >
                        <KeyRound class="size-5" aria-hidden="true" />
                    </span>
                    <DialogTitle>Atur permission {{ role?.name }}</DialogTitle>
                    <DialogDescription class="leading-6">
                        Pilihan disinkronkan secara exact. Permission yang tidak
                        dipilih akan dicabut dari role ini.
                    </DialogDescription>
                </DialogHeader>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold">
                        Permission resmi
                    </legend>
                    <label
                        v-for="permission in permissions"
                        :key="permission.name"
                        :for="`permission-${permission.name}`"
                        class="flex min-h-20 cursor-pointer gap-3 rounded-2xl border p-4 transition-colors duration-200 hover:border-blue-300 hover:bg-blue-50/50 dark:hover:border-blue-800 dark:hover:bg-blue-950/20 [&:has([data-state=checked])]:border-blue-300 [&:has([data-state=checked])]:bg-blue-50/70 dark:[&:has([data-state=checked])]:border-blue-800 dark:[&:has([data-state=checked])]:bg-blue-950/25"
                    >
                        <Checkbox
                            :id="`permission-${permission.name}`"
                            class="mt-1 size-5"
                            :model-value="
                                form.permissions.includes(permission.name)
                            "
                            @update:model-value="
                                togglePermission(
                                    permission.name,
                                    Boolean($event),
                                )
                            "
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">
                                {{ permission.label }}
                            </span>
                            <code
                                class="mt-1 block text-xs break-all text-blue-700 dark:text-blue-300"
                                >{{ permission.name }}</code
                            >
                            <span
                                class="mt-2 block text-sm leading-6 text-muted-foreground"
                            >
                                {{ permission.description }}
                            </span>
                        </span>
                    </label>
                    <InputError :message="form.errors.permissions" />
                </fieldset>

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
                        <Save v-else class="size-4" aria-hidden="true" />
                        Sinkronkan permission
                    </Button>
                </DialogFooter>
            </form>
        </DialogScrollContent>
    </Dialog>
</template>
