<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ confirmPasswordUrl: string }>();
const form = useForm<{ password: string }>({ password: '' });

function submit(): void {
    form.post(props.confirmPasswordUrl, {
        preserveScroll: true,
        onError: () => form.reset('password'),
    });
}
</script>

<template>
    <Alert
        class="mb-6 border-violet-200 bg-violet-50/70 dark:border-violet-900 dark:bg-violet-950/25"
    >
        <KeyRound class="text-violet-700 dark:text-violet-300" />
        <AlertTitle>Step-up security</AlertTitle>
        <AlertDescription class="leading-6">
            Konfirmasi ini membuka mode perubahan administratif selama 15 menit.
            MFA dan authorization tetap diverifikasi pada setiap request.
        </AlertDescription>
    </Alert>

    <form class="space-y-6" @submit.prevent="submit">
        <div class="space-y-2">
            <Label for="back-office-confirm-password"
                >Kata sandi saat ini</Label
            >
            <PasswordInput
                id="back-office-confirm-password"
                v-model="form.password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                :aria-invalid="Boolean(form.errors.password)"
            />
            <InputError :message="form.errors.password" />
        </div>

        <Button
            type="submit"
            class="min-h-11 w-full rounded-xl bg-violet-700 hover:bg-violet-800"
            :disabled="form.processing"
        >
            <Spinner v-if="form.processing" />
            <KeyRound v-else class="size-4" aria-hidden="true" />
            {{
                form.processing
                    ? 'Memverifikasi...'
                    : 'Konfirmasi dan lanjutkan'
            }}
        </Button>
    </form>
</template>
