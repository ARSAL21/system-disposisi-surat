<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, KeyRound, Mail } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa Kata Sandi',
        description:
            'Masukkan alamat email terdaftar untuk menerima tautan pemulihan kata sandi akun.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Lupa Kata Sandi" />

    <div class="space-y-6">
        <Alert
            v-if="status"
            class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
        >
            <CheckCircle2 class="size-4" />
            <AlertDescription class="font-medium">
                {{ status }}
            </AlertDescription>
        </Alert>

        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div class="space-y-1.5">
                <Label
                    for="email"
                    class="text-xs font-semibold text-foreground"
                >
                    Alamat Email
                </Label>
                <div class="relative">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted-foreground"
                    >
                        <Mail class="size-4" />
                    </div>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        autofocus
                        required
                        placeholder="nama@domain.com"
                        class="h-11 rounded-xl pl-10 transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                        :aria-invalid="Boolean(errors.email)"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="pt-2">
                <Button
                    type="submit"
                    class="h-12 w-full gap-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 font-semibold text-white shadow-lg shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 hover:shadow-emerald-600/35 active:scale-[0.99] dark:from-emerald-500 dark:via-teal-600 dark:to-cyan-600"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    <KeyRound v-else class="size-4" />
                    <span>{{
                        processing
                            ? 'Mengirim tautan...'
                            : 'Kirim Tautan Atur Ulang'
                    }}</span>
                </Button>
            </div>
        </Form>

        <div
            class="flex items-center justify-center gap-1 text-center text-xs text-muted-foreground"
        >
            <ArrowLeft class="size-3.5 text-muted-foreground" />
            <span>Kembali ke laman</span>
            <TextLink
                :href="login.url()"
                class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400"
            >
                Masuk akun
            </TextLink>
        </div>
    </div>
</template>
