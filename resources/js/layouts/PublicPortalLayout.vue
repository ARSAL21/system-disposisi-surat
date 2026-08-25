<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    FilePlus2,
    Files,
    LogOut,
    Menu,
    Moon,
    Settings,
    Sun,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import PublicBrand from '@/components/public/PublicBrand.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { useAppearance } from '@/composables/useAppearance';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

const page = usePage();
const mobileOpen = ref(false);
const { resolvedAppearance, updateAppearance } = useAppearance();

const user = computed(() => page.props.auth.user);
const currentPath = computed(() => page.url.split('?')[0]);
const initials = computed(() =>
    user.value.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);

const navigation = [
    { label: 'Dashboard', href: publicSubmissionRoutes.dashboard },
    { label: 'Submission Saya', href: publicSubmissionRoutes.index },
];

function isActive(href: string): boolean {
    if (href === publicSubmissionRoutes.dashboard) {
        return currentPath.value === href;
    }

    return currentPath.value.startsWith(href);
}

function toggleTheme(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}

function handleLogout(): void {
    router.flushAll();
}
</script>

<template>
    <div
        class="public-portal relative min-h-dvh w-full max-w-full overflow-x-hidden bg-background text-foreground"
    >
        <a
            href="#public-main-content"
            class="fixed top-3 left-3 z-[100] -translate-y-20 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground transition-transform focus:translate-y-0"
        >
            Lewati ke konten utama
        </a>

        <div
            class="pointer-events-none fixed inset-0 -z-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -top-28 right-[8%] size-72 rounded-full bg-brand-teal/12 blur-3xl dark:bg-brand-teal/8"
            />
            <div
                class="absolute top-[38rem] -left-24 size-80 rounded-full bg-brand-amber/12 blur-3xl dark:bg-brand-amber/6"
            />
        </div>

        <header class="sticky top-0 z-50 px-3 pt-3 sm:px-5">
            <div
                class="mx-auto flex min-h-16 max-w-7xl items-center gap-4 rounded-[1.4rem] border border-white/55 bg-background/88 px-3 shadow-[0_14px_44px_-30px_rgba(15,54,49,0.55)] backdrop-blur-xl sm:px-5 dark:border-white/8"
            >
                <Link
                    :href="publicSubmissionRoutes.dashboard"
                    class="min-w-0 rounded-xl outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    aria-label="Buka dashboard publik"
                >
                    <PublicBrand />
                </Link>

                <nav
                    class="ml-auto hidden items-center gap-1 lg:flex"
                    aria-label="Navigasi utama"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'inline-flex min-h-11 items-center rounded-xl px-4 text-sm font-medium transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring',
                            isActive(item.href)
                                ? 'bg-brand-teal-soft text-brand-teal-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2 lg:ml-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-11 cursor-pointer rounded-xl"
                        :aria-label="
                            resolvedAppearance === 'dark'
                                ? 'Gunakan tema terang'
                                : 'Gunakan tema gelap'
                        "
                        @click="toggleTheme"
                    >
                        <Sun
                            v-if="resolvedAppearance === 'dark'"
                            class="size-5"
                        />
                        <Moon v-else class="size-5" />
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-11 cursor-pointer rounded-xl"
                                aria-label="Buka menu akun"
                            >
                                <Avatar class="size-9 rounded-xl">
                                    <AvatarFallback
                                        class="rounded-xl bg-primary text-xs font-semibold text-primary-foreground"
                                    >
                                        {{ initials }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-64 rounded-2xl p-2"
                        >
                            <DropdownMenuLabel class="px-2 py-2 font-normal">
                                <span
                                    class="block truncate text-sm font-semibold"
                                >
                                    {{ user.name }}
                                </span>
                                <span
                                    class="mt-0.5 block truncate text-xs text-muted-foreground"
                                >
                                    {{ user.email }}
                                </span>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuGroup>
                                <DropdownMenuItem as-child>
                                    <Link
                                        :href="editProfile()"
                                        class="min-h-10 cursor-pointer rounded-xl"
                                    >
                                        <Settings class="mr-2 size-4" />
                                        Pengaturan akun
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuGroup>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="logout()"
                                    as="button"
                                    class="min-h-10 w-full cursor-pointer rounded-xl text-brand-orange-foreground"
                                    data-test="logout-button"
                                    @click="handleLogout"
                                >
                                    <LogOut class="mr-2 size-4" />
                                    Keluar
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Sheet v-model:open="mobileOpen">
                        <SheetTrigger as-child>
                            <Button
                                variant="outline"
                                size="icon"
                                class="size-11 cursor-pointer rounded-xl lg:hidden"
                                aria-label="Buka navigasi"
                            >
                                <Menu class="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="right"
                            class="w-[min(88vw,23rem)] p-0"
                        >
                            <SheetHeader class="border-b p-6 text-left">
                                <SheetTitle>
                                    <PublicBrand />
                                </SheetTitle>
                                <SheetDescription>
                                    Kelola seluruh submission surat Anda.
                                </SheetDescription>
                            </SheetHeader>
                            <nav
                                class="grid gap-2 p-4"
                                aria-label="Navigasi seluler"
                            >
                                <Link
                                    v-for="item in navigation"
                                    :key="item.href"
                                    :href="item.href"
                                    :class="[
                                        'flex min-h-12 items-center rounded-xl px-4 text-sm font-semibold transition-colors',
                                        isActive(item.href)
                                            ? 'bg-brand-teal-soft text-brand-teal-foreground'
                                            : 'hover:bg-muted',
                                    ]"
                                    @click="mobileOpen = false"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="publicSubmissionRoutes.create"
                                    class="mt-3 flex min-h-12 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground"
                                    @click="mobileOpen = false"
                                >
                                    <FilePlus2 class="size-4" />
                                    Buat submission
                                </Link>
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>

        <main
            id="public-main-content"
            class="relative z-10 outline-none"
            tabindex="-1"
        >
            <slot />
        </main>

        <footer class="relative z-10 border-t bg-card/60">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-10 text-sm text-muted-foreground sm:px-8 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-brand-teal-soft text-brand-teal-foreground"
                        aria-hidden="true"
                    >
                        <Files class="size-4" />
                    </span>
                    <p>
                        Submission online tetap melalui pemeriksaan
                        administratif Bagian Umum.
                    </p>
                </div>
                <p>
                    Dokumen tersimpan privat dan hanya dapat diakses oleh pihak
                    berwenang.
                </p>
            </div>
        </footer>

        <Toaster position="top-right" rich-colors close-button />
    </div>
</template>
