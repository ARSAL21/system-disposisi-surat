<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Save, UserRoundCog } from '@lucide/vue';
import { computed, watch } from 'vue';
import ProtectedRolesNotice from '@/components/back-office/authorization/users/ProtectedRolesNotice.vue';
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
import type { AuthorizationRole, AuthorizationUser } from '@/types';

const props = defineProps<{
    open: boolean;
    user: AuthorizationUser | null;
    roles: AuthorizationRole[];
}>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
const customRoles = computed(() =>
    props.roles.filter((role) => !role.is_protected),
);
const protectedRoles = computed(
    () => props.user?.roles.filter((role) => role.is_protected) ?? [],
);
const initiallyAssignedRoleIds = computed(
    () =>
        props.user?.roles
            .filter((role) => !role.is_protected)
            .map((role) => role.id) ?? [],
);
const userEligibleForNewRoles = computed(() =>
    Boolean(props.user?.is_active && props.user?.is_verified),
);
const form = useForm<{ role_ids: number[] }>({ role_ids: [] });

watch(
    () => [props.open, props.user] as const,
    ([open]) => {
        if (!open) {
            return;
        }

        form.role_ids = [...initiallyAssignedRoleIds.value];
        form.clearErrors();
    },
);

function toggleRole(roleId: number, checked: boolean): void {
    form.role_ids = checked
        ? [...new Set([...form.role_ids, roleId])]
        : form.role_ids.filter((id) => id !== roleId);
}

function roleIsDisabled(roleId: number): boolean {
    return (
        !userEligibleForNewRoles.value &&
        !initiallyAssignedRoleIds.value.includes(roleId)
    );
}

function submit(): void {
    if (!props.user) {
        return;
    }

    form.put(props.user.links.roles, {
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
                        class="mb-2 flex size-11 items-center justify-center rounded-2xl bg-violet-600/10 text-violet-700 dark:text-violet-300"
                    >
                        <UserRoundCog class="size-5" aria-hidden="true" />
                    </span>
                    <DialogTitle>Atur role {{ user?.name }}</DialogTitle>
                    <DialogDescription class="leading-6">
                        Beberapa role dapat diberikan sekaligus. Protected role
                        dipertahankan dan tidak dikirim sebagai input perubahan.
                    </DialogDescription>
                </DialogHeader>

                <ProtectedRolesNotice :roles="protectedRoles" />

                <div
                    v-if="!userEligibleForNewRoles"
                    class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/25 dark:text-amber-100"
                >
                    Akun ini tidak aktif atau belum terverifikasi. Role yang
                    sudah ada tetap dapat dicabut, tetapi role baru tidak dapat
                    diberikan.
                </div>

                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold">Custom role</legend>
                    <label
                        v-for="role in customRoles"
                        :key="role.id"
                        :for="`user-role-${role.id}`"
                        class="flex min-h-16 gap-3 rounded-2xl border p-4 transition-colors duration-200"
                        :class="
                            roleIsDisabled(role.id)
                                ? 'cursor-not-allowed bg-muted/40 opacity-60'
                                : 'cursor-pointer hover:border-violet-300 hover:bg-violet-50/50 dark:hover:border-violet-800 dark:hover:bg-violet-950/20'
                        "
                    >
                        <Checkbox
                            :id="`user-role-${role.id}`"
                            class="mt-0.5 size-5"
                            :disabled="roleIsDisabled(role.id)"
                            :model-value="form.role_ids.includes(role.id)"
                            @update:model-value="
                                toggleRole(role.id, Boolean($event))
                            "
                        />
                        <span>
                            <span class="block text-sm font-semibold">{{
                                role.name
                            }}</span>
                            <span
                                class="mt-1 block text-sm text-muted-foreground"
                            >
                                {{ role.permissions.length }} permission ·
                                {{ role.user_count }} pengguna
                            </span>
                        </span>
                    </label>
                    <p
                        v-if="!customRoles.length"
                        class="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        Belum ada custom role yang dapat diberikan.
                    </p>
                    <InputError :message="form.errors.role_ids" />
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
                        Simpan assignment
                    </Button>
                </DialogFooter>
            </form>
        </DialogScrollContent>
    </Dialog>
</template>
