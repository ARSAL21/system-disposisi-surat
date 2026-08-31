<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { KeyRound, Mail, ShieldCheck } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Atur Ulang Kata Sandi',
        description:
            'Silakan tentukan kata sandi baru yang kuat untuk mengamankan akun Anda.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Atur Ulang Kata Sandi" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="space-y-4.5"
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
                    v-model="inputEmail"
                    readonly
                    class="h-11 rounded-xl bg-muted/50 pl-10 text-muted-foreground"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <div class="space-y-1.5">
            <Label
                for="password"
                class="text-xs font-semibold text-foreground"
            >
                Kata Sandi Baru
            </Label>
            <PasswordInput
                id="password"
                name="password"
                autocomplete="new-password"
                autofocus
                placeholder="Minimal 8 karakter aman"
                :passwordrules="passwordRules"
                class="h-11 rounded-xl transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                :aria-invalid="Boolean(errors.password)"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="space-y-1.5">
            <Label
                for="password_confirmation"
                class="text-xs font-semibold text-foreground"
            >
                Konfirmasi Kata Sandi Baru
            </Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                placeholder="Ulangi kata sandi baru"
                :passwordrules="passwordRules"
                class="h-11 rounded-xl transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                :aria-invalid="Boolean(errors.password_confirmation)"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <div class="pt-2">
            <Button
                type="submit"
                class="h-12 w-full gap-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 font-semibold text-white shadow-lg shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 hover:shadow-emerald-600/35 active:scale-[0.99] dark:from-emerald-500 dark:via-teal-600 dark:to-cyan-600"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                <KeyRound v-else class="size-4" />
                <span>{{
                    processing
                        ? 'Menyimpan kata sandi...'
                        : 'Simpan Kata Sandi Baru'
                }}</span>
            </Button>
        </div>

        <div
            class="flex items-center justify-center gap-1.5 pt-2 text-[11px] text-muted-foreground/75"
        >
            <ShieldCheck class="size-3.5 text-emerald-500" />
            <span>Sandi baru akan langsung terenkripsi dengan aman</span>
        </div>
    </Form>
</template>
