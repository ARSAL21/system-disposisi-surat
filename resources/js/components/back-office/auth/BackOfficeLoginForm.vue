<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { CheckCircle2, LockKeyhole } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login as publicLogin } from '@/routes';
import backOffice from '@/routes/back-office';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Alert
        v-if="status"
        class="mb-6 border-emerald-500/30 bg-emerald-500/5 text-emerald-700 dark:text-emerald-300"
    >
        <CheckCircle2 />
        <AlertDescription>{{ status }}</AlertDescription>
    </Alert>

    <PasskeyVerify
        :routes="{
            options: backOffice.passkey.loginOptions(),
            submit: backOffice.passkey.login(),
        }"
        label="Masuk dengan passkey"
        loading-label="Memverifikasi passkey..."
        separator="Atau gunakan email"
    />

    <Form
        v-bind="backOffice.login.store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="space-y-6"
    >
        <div class="space-y-2">
            <Label for="email">Email internal</Label>
            <Input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                placeholder="nama@instansi.go.id"
                :aria-invalid="Boolean(errors.email)"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-4">
                <Label for="password">Kata sandi</Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-sm"
                    :tabindex="5"
                >
                    Lupa kata sandi?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="Masukkan kata sandi"
                :aria-invalid="Boolean(errors.password)"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="flex items-center justify-between">
            <Label
                for="remember"
                class="flex cursor-pointer items-center gap-3"
            >
                <Checkbox id="remember" name="remember" :tabindex="3" />
                <span>Ingat sesi saya</span>
            </Label>
        </div>

        <Button
            type="submit"
            class="h-11 w-full rounded-xl"
            :tabindex="4"
            :disabled="processing"
            data-test="back-office-login-button"
        >
            <Spinner v-if="processing" />
            <LockKeyhole v-else class="size-4" />
            {{ processing ? 'Memeriksa akses...' : 'Masuk ke Back-office' }}
        </Button>
    </Form>

    <div class="mt-7 border-t pt-6 text-center text-sm text-muted-foreground">
        Bukan staf internal?
        <TextLink :href="publicLogin()" class="font-medium">
            Masuk ke portal publik
        </TextLink>
    </div>
</template>
