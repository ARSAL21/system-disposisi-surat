<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { store as defaultConfirmStore } from '@/routes/password/confirm';

export type Props = {
    isOpen: boolean;
    confirmPasswordUrl?: string;
    targetUrl?: string;
    title?: string;
    description?: string;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Konfirmasi Kata Sandi',
    description:
        'Area pengaturan keamanan memerlukan verifikasi kata sandi akun Anda sebelum dapat diakses.',
});

const emit = defineEmits<{
    'update:isOpen': [value: boolean];
    confirmed: [];
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user);

const resolvedUrl = computed(() => {
    if (props.confirmPasswordUrl) {
        return props.confirmPasswordUrl;
    }

    if (user.value?.confirm_password_url) {
        return user.value.confirm_password_url;
    }

    return defaultConfirmStore.url();
});

const form = useForm({
    password: '',
});

const passwordInputRef = ref<InstanceType<typeof PasswordInput> | null>(null);

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            form.reset('password');
            form.clearErrors();
            nextTick(() => {
                passwordInputRef.value?.focus();
            });
        }
    },
);

const handleClose = () => {
    emit('update:isOpen', false);
    form.reset('password');
    form.clearErrors();
};

const submit = () => {
    form.post(resolvedUrl.value, {
        preserveScroll: true,
        onSuccess: () => {
            if (user.value) {
                user.value.password_confirmed = true;
            }

            emit('confirmed');
            emit('update:isOpen', false);

            if (props.targetUrl) {
                router.visit(props.targetUrl);
            }
        },
        onError: () => {
            form.reset('password');
            nextTick(() => {
                passwordInputRef.value?.focus();
            });
        },
    });
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
        <DialogContent class="sm:max-w-md overflow-hidden">
            <!-- Header with Glowing Icon Badge -->
            <DialogHeader class="space-y-3 text-left">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-500/20"
                >
                    <KeyRound class="size-6" />
                </div>
                <DialogTitle class="text-xl font-bold tracking-tight text-foreground">
                    {{ title }}
                </DialogTitle>
                <DialogDescription class="text-xs leading-relaxed text-muted-foreground">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <!-- Optional Passkey Verification for supported devices -->
            <div class="pt-1">
                <PasskeyVerify
                    :routes="{
                        options: confirmOptions(),
                        submit: confirmStore(),
                    }"
                    label="Konfirmasi dengan Passkey / Biometrik"
                    loading-label="Memverifikasi Passkey..."
                    separator="Atau konfirmasi dengan kata sandi"
                />
            </div>

            <!-- Password Form -->
            <form @submit.prevent="submit" class="space-y-5">
                <div class="space-y-2">
                    <Label for="confirm-modal-password" class="text-xs font-semibold text-foreground">
                        Kata Sandi Akun
                    </Label>
                    <PasswordInput
                        id="confirm-modal-password"
                        ref="passwordInputRef"
                        name="password"
                        v-model="form.password"
                        required
                        autofocus
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi akun Anda"
                        :aria-invalid="Boolean(form.errors.password)"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div
                    class="flex items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 text-[11px] text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-200"
                >
                    <ShieldCheck class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                    <span>
                        Konfirmasi ini membuka mode akses keamanan selama sesi aktif.
                    </span>
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
                        class="gap-2 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    >
                        <Spinner v-if="form.processing" />
                        <KeyRound v-else class="size-4" />
                        {{ form.processing ? 'Memverifikasi...' : 'Konfirmasi & Lanjutkan' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
