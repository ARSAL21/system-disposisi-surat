<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import { nextTick, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = withDefaults(
    defineProps<{
        open: boolean;
        confirmPasswordUrl?: string;
        title?: string;
        description?: string;
    }>(),
    {
        confirmPasswordUrl: '/back-office/confirm-password',
        title: 'Konfirmasi akses administratif',
        description:
            'Area ini memerlukan verifikasi ulang sebelum data administratif sensitif dapat diubah.',
    },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirmed: [];
}>();

const form = useForm({
    password: '',
});

const passwordInputRef = ref<InstanceType<typeof PasswordInput> | null>(null);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.reset('password');
            form.clearErrors();
            nextTick(() => {
                passwordInputRef.value?.focus();
            });
        }
    },
);

function handleClose(): void {
    emit('update:open', false);
    form.reset('password');
    form.clearErrors();
}

function submit(): void {
    form.post(props.confirmPasswordUrl, {
        preserveScroll: true,
        onSuccess: () => {
            emit('confirmed');
            emit('update:open', false);
            form.reset('password');
            toast.success(
                'Mode perubahan administratif aktif selama 15 menit.',
            );
        },
        onError: () => {
            form.reset('password');
            nextTick(() => {
                passwordInputRef.value?.focus();
            });
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="overflow-hidden sm:max-w-md">
            <DialogHeader class="space-y-3 text-left">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-700 text-white shadow-md shadow-violet-500/20"
                >
                    <KeyRound class="size-6" />
                </div>
                <DialogTitle
                    class="text-xl font-bold tracking-tight text-foreground"
                >
                    {{ title }}
                </DialogTitle>
                <DialogDescription
                    class="text-xs leading-relaxed text-muted-foreground"
                >
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <Alert
                class="border-violet-200 bg-violet-50/70 dark:border-violet-900/60 dark:bg-violet-950/25"
            >
                <KeyRound class="size-4 text-violet-700 dark:text-violet-300" />
                <AlertTitle class="text-violet-950 dark:text-violet-100">
                    Step-up security
                </AlertTitle>
                <AlertDescription
                    class="leading-6 text-violet-800 dark:text-violet-200"
                >
                    Konfirmasi ini membuka mode perubahan administratif selama
                    15 menit. MFA dan authorization tetap diverifikasi pada
                    setiap request.
                </AlertDescription>
            </Alert>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <Label
                        for="back-office-modal-password"
                        class="text-xs font-semibold text-foreground"
                    >
                        Kata sandi saat ini
                    </Label>
                    <PasswordInput
                        id="back-office-modal-password"
                        ref="passwordInputRef"
                        v-model="form.password"
                        name="password"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi akun Anda"
                        :aria-invalid="Boolean(form.errors.password)"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <DialogFooter class="gap-2 pt-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            @click="handleClose"
                        >
                            Batal
                        </Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        :disabled="form.processing || !form.password"
                        class="gap-2 bg-violet-700 font-medium text-white shadow-xs hover:bg-violet-800 dark:bg-violet-600 dark:hover:bg-violet-500"
                    >
                        <Spinner v-if="form.processing" />
                        <KeyRound v-else class="size-4" aria-hidden="true" />
                        {{
                            form.processing
                                ? 'Memverifikasi...'
                                : 'Konfirmasi & Lanjutkan'
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
