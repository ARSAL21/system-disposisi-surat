<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    Mail,
    Save,
    Shield,
    User as UserIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const isInternal = computed(() => user.value.account_type === 'INTERNAL');
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="space-y-8">
        <!-- Identity Summary Card -->
        <div
            class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 via-white to-violet-50/40 p-5 shadow-xs sm:p-6 dark:border-indigo-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20"
        >
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <Avatar class="size-16 rounded-2xl border-2 border-white shadow-md dark:border-slate-800">
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-600 text-lg font-bold text-white shadow-inner"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-foreground">
                                {{ user.name }}
                            </h2>
                            <Badge
                                v-if="user.email_verified_at"
                                variant="outline"
                                class="border-emerald-200 bg-emerald-50/80 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                            >
                                <CheckCircle2 class="mr-1 size-3 text-emerald-600" />
                                Terverifikasi
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-amber-200 bg-amber-50/80 text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                            >
                                <AlertCircle class="mr-1 size-3 text-amber-600" />
                                Belum Terverifikasi
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 self-start sm:self-center">
                    <Badge
                        variant="secondary"
                        :class="[
                            'px-3 py-1 text-xs font-semibold',
                            isInternal
                                ? 'bg-indigo-100/80 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300'
                                : 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300',
                        ]"
                    >
                        <Shield class="mr-1.5 size-3.5" />
                        {{ isInternal ? 'Akun Internal Pemkot' : 'Akun Publik Eksternal' }}
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="space-y-6">
            <div class="flex items-center gap-3 border-b border-border/60 pb-4">
                <div
                    class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300"
                >
                    <UserIcon class="size-4.5" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-foreground">
                        Informasi Profil
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Perbarui nama lengkap dan alamat email resmi Anda
                    </p>
                </div>
            </div>

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name" class="text-sm font-medium text-foreground">
                        Nama Lengkap
                    </Label>
                    <div class="relative">
                        <Input
                            id="name"
                            class="block w-full pl-10 transition-colors focus-visible:ring-indigo-500"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Masukkan nama lengkap Anda"
                        />
                        <UserIcon
                            class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                    </div>
                    <InputError class="mt-1" :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email" class="text-sm font-medium text-foreground">
                        Alamat Email
                    </Label>
                    <div class="relative">
                        <Input
                            id="email"
                            type="email"
                            class="block w-full pl-10 transition-colors focus-visible:ring-indigo-500"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="nama@domain.com"
                        />
                        <Mail
                            class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                    </div>
                    <InputError class="mt-1" :message="errors.email" />
                </div>

                <!-- Unverified email notice banner -->
                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                    class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/40 dark:bg-amber-950/30"
                >
                    <div class="flex items-start gap-3">
                        <AlertCircle class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div class="space-y-1 text-sm text-amber-900 dark:text-amber-200">
                            <p class="font-medium">
                                Alamat email Anda belum diverifikasi.
                            </p>
                            <p class="text-xs text-amber-800/90 dark:text-amber-300/80">
                                Harap verifikasi email Anda agar dapat menggunakan seluruh layanan.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="font-semibold underline decoration-amber-400 underline-offset-2 transition-colors hover:text-amber-950 dark:hover:text-white"
                                >
                                    Klik di sini untuk mengirim ulang tautan verifikasi.
                                </Link>
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="page.props.status === 'verification-link-sent'"
                        class="mt-3 flex items-center gap-2 rounded-lg bg-emerald-100/70 px-3 py-2 text-xs font-medium text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200"
                    >
                        <CheckCircle2 class="size-4 text-emerald-600 dark:text-emerald-400" />
                        Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda.
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="update-profile-button"
                        class="gap-2 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    >
                        <Save class="size-4" />
                        Simpan Perubahan
                    </Button>
                </div>
            </Form>
        </div>

        <div class="border-t border-border/60 pt-6">
            <DeleteUser />
        </div>
    </div>
</template>
