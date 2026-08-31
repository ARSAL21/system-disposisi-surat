<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    LockKeyhole,
    Mail,
    Shield,
} from '@lucide/vue';
import gsap from 'gsap';
import { onMounted, ref } from 'vue';
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

const formContainerRef = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!formContainerRef.value) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const ctx = gsap.context(() => {
        gsap.from('.login-animate-item', {
            opacity: 0,
            y: 18,
            duration: 0.65,
            stagger: 0.08,
            ease: 'power3.out',
        });
    }, formContainerRef.value);

    return () => ctx.revert();
});
</script>

<template>
    <div ref="formContainerRef" class="w-full space-y-6">
        <Alert
            v-if="status"
            class="login-animate-item border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
        >
            <CheckCircle2 class="size-4" />
            <AlertDescription class="font-medium">{{
                status
            }}</AlertDescription>
        </Alert>

        <div class="login-animate-item">
            <PasskeyVerify
                :routes="{
                    options: backOffice.passkey.loginOptions(),
                    submit: backOffice.passkey.login(),
                }"
                label="Masuk dengan Passkey / Biometrik"
                loading-label="Memverifikasi passkey..."
                separator="Atau gunakan kredensial akun internal"
            />
        </div>

        <Form
            v-bind="backOffice.login.store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div class="login-animate-item space-y-2">
                <Label
                    for="email"
                    class="text-xs font-semibold text-foreground"
                >
                    Email Dinas Internal
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
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="nama@instansi.go.id"
                        class="h-11 rounded-xl pl-10 transition-all focus-visible:ring-2 focus-visible:ring-indigo-500/30"
                        :aria-invalid="Boolean(errors.email)"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <div class="login-animate-item space-y-2">
                <div class="flex items-center justify-between gap-4">
                    <Label
                        for="password"
                        class="text-xs font-semibold text-foreground"
                    >
                        Kata Sandi
                    </Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
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
                    placeholder="Masukkan kata sandi akun"
                    class="h-11 rounded-xl transition-all focus-visible:ring-2 focus-visible:ring-indigo-500/30"
                    :aria-invalid="Boolean(errors.password)"
                />
                <InputError :message="errors.password" />
            </div>

            <div
                class="login-animate-item flex items-center justify-between pt-1"
            >
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center gap-2.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
                >
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Ingat sesi perangkat ini</span>
                </Label>
            </div>

            <div class="login-animate-item pt-2">
                <Button
                    type="submit"
                    class="h-12 w-full gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-700 font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all duration-300 hover:from-indigo-500 hover:via-indigo-600 hover:to-violet-600 hover:shadow-indigo-600/35 active:scale-[0.99] dark:from-indigo-500 dark:via-indigo-600 dark:to-violet-600"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="back-office-login-button"
                >
                    <Spinner v-if="processing" />
                    <LockKeyhole v-else class="size-4.5" />
                    <span>{{
                        processing
                            ? 'Memverifikasi kredensial...'
                            : 'Masuk ke Portal Internal'
                    }}</span>
                </Button>
            </div>
        </Form>

        <div
            class="login-animate-item mt-8 rounded-2xl border border-border/70 bg-card/60 p-4 text-center text-xs text-muted-foreground backdrop-blur-sm"
        >
            <span>Bukan aparatur / staf internal?</span>
            <TextLink
                :href="publicLogin()"
                class="group ml-1.5 inline-flex items-center gap-1 font-semibold text-primary transition-colors hover:underline"
            >
                <span>Masuk ke portal publik</span>
                <ArrowRight
                    class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5"
                />
            </TextLink>
        </div>

        <div
            class="login-animate-item flex items-center justify-center gap-2 pt-2 text-[11px] text-muted-foreground/75"
        >
            <Shield class="size-3.5 text-emerald-500" />
            <span>Terproteksi Zero-Trust & Enkripsi Sesi TLS 1.3</span>
        </div>
    </div>
</template>
