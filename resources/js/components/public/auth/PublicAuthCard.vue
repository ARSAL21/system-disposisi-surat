<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    CheckCircle2,
    FileText,
    LogIn,
    Mail,
    Shield,
    Sparkles,
    User as UserIcon,
    UserPlus,
} from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';
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
import { store as loginStore } from '@/routes/login';
import { request as passwordResetRequest } from '@/routes/password';
import { store as registerStore } from '@/routes/register';

const props = withDefaults(
    defineProps<{
        initialMode?: 'login' | 'register';
        canResetPassword?: boolean;
        status?: string;
        passwordRules?: string;
    }>(),
    {
        initialMode: 'login',
        canResetPassword: true,
        status: undefined,
        passwordRules: '',
    },
);

const mode = ref<'login' | 'register'>(props.initialMode);
const transitionDirection = ref<'slide-left' | 'slide-right'>('slide-left');

function setMode(newMode: 'login' | 'register'): void {
    if (mode.value === newMode) {
return;
}

    transitionDirection.value =
        newMode === 'register' ? 'slide-left' : 'slide-right';
    mode.value = newMode;

    const targetUrl = newMode === 'register' ? '/register' : '/login';
    window.history.replaceState({ mode: newMode }, '', targetUrl);
}

function handlePopState(): void {
    if (window.location.pathname.includes('/register')) {
        transitionDirection.value = 'slide-left';
        mode.value = 'register';
    } else {
        transitionDirection.value = 'slide-right';
        mode.value = 'login';
    }
}

onMounted(() => {
    window.addEventListener('popstate', handlePopState);
});

onUnmounted(() => {
    window.removeEventListener('popstate', handlePopState);
});
</script>

<template>
    <Head :title="mode === 'login' ? 'Masuk Akun' : 'Daftar Akun Baru'" />

    <div class="relative w-full">
        <!-- Minimalist Aesthetic Header / Brand Badge (No Laravel Logo) -->
        <div class="mb-4 flex flex-col items-center text-center">
            <div
                class="mb-2.5 flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 text-white shadow-md shadow-emerald-500/20 ring-1 ring-white/20 transition-transform duration-500 hover:scale-105"
            >
                <FileText class="size-5.5 text-white" />
            </div>

            <div
                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300"
            >
                <Sparkles class="size-3" />
                <span>Layanan Mandiri Masyarakat</span>
            </div>

            <h1
                class="mt-2 text-xl font-bold tracking-tight text-foreground sm:text-2xl"
            >
                {{ mode === 'login' ? 'Selamat Datang' : 'Buat Akun Baru' }}
            </h1>
            <p class="mt-0.5 text-xs text-muted-foreground">
                {{
                    mode === 'login'
                        ? 'Masuk untuk mengajukan surat dan memantau disposisi'
                        : 'Lengkapi data untuk mendapatkan akses pengajuan surat'
                }}
            </p>
        </div>

        <!-- Interactive Animated Segmented Pill Switcher -->
        <div class="mb-4">
            <div
                class="relative flex w-full rounded-2xl border border-border/70 bg-muted/50 p-1 backdrop-blur-sm"
                role="tablist"
                aria-label="Pilihan Masuk atau Daftar"
            >
                <!-- Animated Active Indicator Pill -->
                <div
                    class="absolute top-1 bottom-1 rounded-xl bg-background shadow-md shadow-black/5 transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] dark:bg-slate-800 dark:shadow-black/20"
                    :class="
                        mode === 'login'
                            ? 'left-1 w-[calc(50%-4px)]'
                            : 'left-[calc(50%+2px)] w-[calc(50%-4px)]'
                    "
                    aria-hidden="true"
                />

                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'login'"
                    class="relative z-10 flex flex-1 items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold transition-colors duration-200"
                    :class="
                        mode === 'login'
                            ? 'text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="setMode('login')"
                >
                    <LogIn class="size-3.5" />
                    <span>Masuk</span>
                </button>

                <button
                    type="button"
                    role="tab"
                    :aria-selected="mode === 'register'"
                    class="relative z-10 flex flex-1 items-center justify-center gap-2 rounded-xl py-2 text-xs font-semibold transition-colors duration-200"
                    :class="
                        mode === 'register'
                            ? 'text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="setMode('register')"
                >
                    <UserPlus class="size-3.5" />
                    <span>Daftar</span>
                </button>
            </div>
        </div>

        <!-- Smooth Transition Form Wrapper -->
        <Transition :name="transitionDirection" mode="out-in">
            <!-- ===================== LOGIN FORM ===================== -->
            <div v-if="mode === 'login'" key="login-view" class="space-y-4">
                <Alert
                    v-if="status"
                    class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                >
                    <CheckCircle2 class="size-4" />
                    <AlertDescription class="font-medium">{{
                        status
                    }}</AlertDescription>
                </Alert>

                <!-- Passkey Verification -->
                <div>
                    <PasskeyVerify />
                </div>

                <Form
                    v-bind="loginStore.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="space-y-3.5"
                >
                    <div class="space-y-1">
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
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="email"
                                placeholder="nama@domain.com"
                                class="h-10 rounded-xl pl-10 text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                                :aria-invalid="Boolean(errors.email)"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between gap-4">
                            <Label
                                for="password"
                                class="text-xs font-semibold text-foreground"
                            >
                                Kata Sandi
                            </Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="passwordResetRequest.url()"
                                class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
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
                            class="h-10 rounded-xl text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                            :aria-invalid="Boolean(errors.password)"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex items-center justify-between pt-0.5">
                        <Label
                            for="remember"
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <Checkbox
                                id="remember"
                                name="remember"
                                :tabindex="3"
                            />
                            <span>Ingat sesi perangkat ini</span>
                        </Label>
                    </div>

                    <div class="pt-1.5">
                        <Button
                            type="submit"
                            class="h-11 w-full gap-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 font-semibold text-white shadow-md shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 hover:shadow-emerald-600/35 active:scale-[0.99] dark:from-emerald-500 dark:via-teal-600 dark:to-cyan-600"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" />
                            <LogIn v-else class="size-4" />
                            <span>{{
                                processing
                                    ? 'Memverifikasi akun...'
                                    : 'Masuk ke Akun'
                            }}</span>
                        </Button>
                    </div>
                </Form>
            </div>

            <!-- ===================== REGISTER FORM ===================== -->
            <div v-else key="register-view" class="space-y-3.5">
                <Form
                    v-bind="registerStore.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="space-y-3"
                >
                    <div class="space-y-1">
                        <Label
                            for="name"
                            class="text-xs font-semibold text-foreground"
                        >
                            Nama Lengkap
                        </Label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-muted-foreground"
                            >
                                <UserIcon class="size-4" />
                            </div>
                            <Input
                                id="name"
                                type="text"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="name"
                                name="name"
                                placeholder="Nama lengkap sesuai KTP"
                                class="h-10 rounded-xl pl-10 text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                                :aria-invalid="Boolean(errors.name)"
                            />
                        </div>
                        <InputError :message="errors.name" />
                    </div>

                    <div class="space-y-1">
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
                                required
                                :tabindex="2"
                                autocomplete="email"
                                name="email"
                                placeholder="nama@domain.com"
                                class="h-10 rounded-xl pl-10 text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                                :aria-invalid="Boolean(errors.email)"
                            />
                        </div>
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-1">
                        <Label
                            for="password"
                            class="text-xs font-semibold text-foreground"
                        >
                            Kata Sandi
                        </Label>
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Minimal 8 karakter aman"
                            :passwordrules="passwordRules"
                            class="h-10 rounded-xl text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                            :aria-invalid="Boolean(errors.password)"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="space-y-1">
                        <Label
                            for="password_confirmation"
                            class="text-xs font-semibold text-foreground"
                        >
                            Konfirmasi Kata Sandi
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Ulangi kata sandi di atas"
                            :passwordrules="passwordRules"
                            class="h-10 rounded-xl text-sm transition-all focus-visible:ring-2 focus-visible:ring-emerald-500/30"
                            :aria-invalid="
                                Boolean(errors.password_confirmation)
                            "
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="pt-1.5">
                        <Button
                            type="submit"
                            class="h-11 w-full gap-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 font-semibold text-white shadow-md shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 hover:shadow-emerald-600/35 active:scale-[0.99] dark:from-emerald-500 dark:via-teal-600 dark:to-cyan-600"
                            tabindex="5"
                            :disabled="processing"
                            data-test="register-user-button"
                        >
                            <Spinner v-if="processing" />
                            <UserPlus v-else class="size-4" />
                            <span>{{
                                processing
                                    ? 'Membuat akun...'
                                    : 'Daftar Akun Baru'
                            }}</span>
                        </Button>
                    </div>
                </Form>
            </div>
        </Transition>

        <div
            class="mt-4 flex items-center justify-center gap-1.5 text-[11px] text-muted-foreground/75"
        >
            <Shield class="size-3.5 text-emerald-500" />
            <span>Kerahasiaan data pemohon surat terproteksi penuh</span>
        </div>
    </div>
</template>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(16px) scale(0.99);
    filter: blur(3px);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-16px) scale(0.99);
    filter: blur(3px);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-16px) scale(0.99);
    filter: blur(3px);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(16px) scale(0.99);
    filter: blur(3px);
}
</style>
