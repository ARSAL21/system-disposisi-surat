<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Lock,
    Palette,
    Shield,
    ShieldCheck,
    SlidersHorizontal,
    Sparkles,
    User,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmPasswordModal from '@/components/ConfirmPasswordModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

interface SettingsNavItem extends NavItem {
    id: 'profile' | 'security' | 'appearance';
    description?: string;
    badge?: string;
}

const navigationTabs: SettingsNavItem[] = [
    {
        id: 'profile',
        title: 'Profil',
        href: editProfile(),
        icon: User,
        description: 'Data akun & email',
    },
    {
        id: 'security',
        title: 'Keamanan',
        href: editSecurity(),
        icon: ShieldCheck,
        description: 'Kata sandi, 2FA & passkey',
    },
    {
        id: 'appearance',
        title: 'Tampilan',
        href: editAppearance(),
        icon: Palette,
        description: 'Tema & preferensi visual',
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const { isCurrentOrParentUrl } = useCurrentUrl();

const isInternal = computed(() => user.value.account_type === 'INTERNAL');
const isConfirmPasswordOpen = ref(false);

const handlePasswordConfirmed = () => {
    router.visit(toUrl(editSecurity()) || '/settings/security', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header Banner with Gradient Mesh -->
        <div
            class="relative overflow-hidden rounded-2xl border border-indigo-100/80 bg-gradient-to-r from-white via-indigo-50/40 to-violet-50/50 p-6 shadow-sm sm:p-8 dark:border-indigo-900/30 dark:from-slate-950 dark:via-indigo-950/20 dark:to-violet-950/30"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-12 -right-12 size-48 rounded-full bg-gradient-to-br from-indigo-400/15 to-violet-400/10 blur-2xl dark:from-indigo-500/10 dark:to-violet-500/10"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-8 left-1/3 size-36 rounded-full bg-blue-400/10 blur-2xl dark:bg-blue-600/10"
            />

            <div
                class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Badge
                            variant="outline"
                            class="border-indigo-200/80 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300"
                        >
                            <SlidersHorizontal class="mr-1 size-3" />
                            Account Settings
                        </Badge>
                        <Badge
                            v-if="isInternal"
                            variant="secondary"
                            class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <Shield class="mr-1 size-3" />
                            Internal
                        </Badge>
                        <Badge
                            v-else
                            variant="secondary"
                            class="bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            Public User
                        </Badge>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Pengaturan & Preferensi
                    </h1>
                    <p class="max-w-xl text-sm leading-relaxed text-muted-foreground">
                        Kelola data profil, tingkatkan keamanan akun, dan sesuaikan
                        tampilan sesuai kenyamanan kerja Anda.
                    </p>
                </div>

                <!-- User identity summary card -->
                <div
                    class="flex items-center gap-3.5 self-start rounded-xl border border-black/5 bg-white/80 p-3 shadow-xs backdrop-blur-md sm:self-center dark:border-white/10 dark:bg-slate-900/80"
                >
                    <div class="relative">
                        <Avatar class="size-11 rounded-xl border-2 border-indigo-200 dark:border-indigo-800">
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="user.name"
                            />
                            <AvatarFallback
                                class="rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 font-semibold text-white"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-900"
                        />
                    </div>
                    <div class="space-y-0.5 text-left">
                        <div class="font-medium text-sm text-foreground">
                            {{ user.name }}
                        </div>
                        <div class="text-xs text-muted-foreground truncate max-w-[160px]">
                            {{ user.email }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Horizontal Top Tab Navigation Bar -->
        <nav
            class="grid grid-cols-1 gap-2.5 rounded-2xl border border-black/5 bg-neutral-100/80 p-2 shadow-xs backdrop-blur-md sm:grid-cols-3 dark:border-white/10 dark:bg-slate-900/90"
            aria-label="Settings navigation tabs"
        >
            <template v-for="item in navigationTabs" :key="item.id">
                <!-- If tab is Security and password is NOT confirmed in session, render as a button that directly opens modal without triggering Link navigation -->
                <button
                    v-if="item.id === 'security' && !user?.password_confirmed"
                    type="button"
                    @click="isConfirmPasswordOpen = true"
                    :class="[
                        'group relative flex items-center gap-3.5 rounded-xl p-3.5 transition-all duration-200 cursor-pointer text-left focus:outline-none',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-white shadow-xs ring-1 ring-black/5 dark:bg-slate-800 dark:ring-white/10'
                            : 'hover:bg-white/60 dark:hover:bg-slate-800/50',
                    ]"
                >
                    <!-- Active bottom indicator gradient line -->
                    <div
                        v-if="isCurrentOrParentUrl(item.href)"
                        class="absolute inset-x-4 bottom-0 h-0.5 rounded-t-full bg-gradient-to-r from-indigo-600 to-violet-600"
                    />

                    <!-- Icon container -->
                    <div
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-all duration-200',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-xs shadow-indigo-500/30'
                                : 'bg-white text-neutral-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-slate-950 dark:text-neutral-300 dark:group-hover:bg-indigo-950/60 dark:group-hover:text-indigo-300',
                        ]"
                    >
                        <component :is="item.icon" class="size-5" />
                    </div>

                    <!-- Text labels -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'text-sm leading-tight transition-colors',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'font-bold text-foreground'
                                        : 'font-medium text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ item.title }}
                            </span>
                            <Lock class="size-3.5 text-amber-500" />
                        </div>
                        <p
                            v-if="item.description"
                            class="mt-0.5 text-xs text-muted-foreground/80 truncate"
                        >
                            {{ item.description }}
                        </p>
                    </div>
                </button>

                <!-- Otherwise, render standard Inertia Link for confirmed/other tabs -->
                <Link
                    v-else
                    :href="item.href"
                    :class="[
                        'group relative flex items-center gap-3.5 rounded-xl p-3.5 transition-all duration-200 cursor-pointer text-left',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-white shadow-xs ring-1 ring-black/5 dark:bg-slate-800 dark:ring-white/10'
                            : 'hover:bg-white/60 dark:hover:bg-slate-800/50',
                    ]"
                >
                    <!-- Active bottom indicator gradient line -->
                    <div
                        v-if="isCurrentOrParentUrl(item.href)"
                        class="absolute inset-x-4 bottom-0 h-0.5 rounded-t-full bg-gradient-to-r from-indigo-600 to-violet-600"
                    />

                    <!-- Icon container -->
                    <div
                        :class="[
                            'flex size-10 shrink-0 items-center justify-center rounded-xl transition-all duration-200',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-xs shadow-indigo-500/30'
                                : 'bg-white text-neutral-600 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-slate-950 dark:text-neutral-300 dark:group-hover:bg-indigo-950/60 dark:group-hover:text-indigo-300',
                        ]"
                    >
                        <component :is="item.icon" class="size-5" />
                    </div>

                    <!-- Text labels -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'text-sm leading-tight transition-colors',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'font-bold text-foreground'
                                        : 'font-medium text-muted-foreground group-hover:text-foreground',
                                ]"
                            >
                                {{ item.title }}
                            </span>
                            <Sparkles
                                v-if="isCurrentOrParentUrl(item.href)"
                                class="size-3.5 text-indigo-500 animate-pulse"
                            />
                        </div>
                        <p
                            v-if="item.description"
                            class="mt-0.5 text-xs text-muted-foreground/80 truncate"
                        >
                            {{ item.description }}
                        </p>
                    </div>
                </Link>
            </template>
        </nav>

        <!-- Main Content Area -->
        <main
            class="rounded-2xl border border-black/5 bg-white p-6 shadow-xs sm:p-8 dark:border-white/10 dark:bg-slate-900"
        >
            <div class="mx-auto max-w-4xl space-y-10">
                <slot />
            </div>
        </main>

        <!-- Interactive Password Confirmation Modal for Security Menu -->
        <ConfirmPasswordModal
            v-model:isOpen="isConfirmPasswordOpen"
            :targetUrl="toUrl(editSecurity()) || '/settings/security'"
            @confirmed="handlePasswordConfirmed"
        />
    </div>
</template>
