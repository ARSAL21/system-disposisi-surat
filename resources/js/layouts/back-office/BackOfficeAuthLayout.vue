<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Moon, ShieldCheck, Sun } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useAppearance } from '@/composables/useAppearance';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const appName = usePage().props.name;
const { resolvedAppearance, updateAppearance } = useAppearance();

function toggleAppearance(): void {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <div
        class="relative flex min-h-svh w-full flex-col justify-between overflow-x-hidden bg-background p-4 sm:p-6 lg:p-8"
    >
        <!-- Atmospheric Mesh & Dynamic Ambient Blurs -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-gradient-to-b from-indigo-500/20 via-blue-600/15 to-transparent blur-[120px] dark:from-indigo-600/25 dark:via-blue-600/20"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -bottom-32 left-1/2 size-[32rem] -translate-x-1/2 rounded-full bg-gradient-to-t from-violet-500/20 via-cyan-600/10 to-transparent blur-[120px] dark:from-violet-600/20 dark:via-cyan-600/15"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(99,102,241,0.08),transparent_60%)]"
        />

        <!-- Subtle Grid Pattern Overlay -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 [background-image:linear-gradient(to_right,#000000_1px,transparent_1px),linear-gradient(to_bottom,#000000_1px,transparent_1px)] [background-size:36px_36px] opacity-[0.025] dark:[background-image:linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] dark:opacity-[0.04]"
        />

        <!-- Top Header Navigation -->
        <header
            class="relative z-20 mx-auto flex w-full max-w-xl items-center justify-between py-2"
        >
            <Link
                :href="home.url()"
                class="group flex items-center gap-2.5 rounded-xl p-1 font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span
                    class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/20 transition-transform duration-300 group-hover:scale-105"
                >
                    <AppLogoIcon class="size-5 text-white" />
                </span>
                <div>
                    <span
                        class="block text-sm font-bold tracking-tight text-foreground"
                    >
                        {{ appName }}
                    </span>
                    <span
                        class="block text-[11px] font-medium text-muted-foreground"
                    >
                        Portal Internal
                    </span>
                </div>
            </Link>

            <div class="flex items-center gap-3">
                <div
                    class="hidden items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 sm:flex dark:text-emerald-300"
                >
                    <span class="relative flex size-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        />
                        <span
                            class="relative inline-flex size-2 rounded-full bg-emerald-500"
                        />
                    </span>
                    Sistem Operasional
                </div>

                <Button
                    variant="outline"
                    size="icon"
                    class="size-10 rounded-xl border-border/80 bg-card/70 backdrop-blur-md transition-transform hover:scale-105"
                    :aria-label="
                        resolvedAppearance === 'dark'
                            ? 'Gunakan tema terang'
                            : 'Gunakan tema gelap'
                    "
                    @click="toggleAppearance"
                >
                    <Sun
                        v-if="resolvedAppearance === 'dark'"
                        class="size-4.5 text-amber-400"
                    />
                    <Moon v-else class="size-4.5 text-indigo-600" />
                </Button>
            </div>
        </header>

        <!-- Main Single Center Section / Elevated Glass Card -->
        <main
            class="relative z-10 mx-auto my-auto w-full max-w-[28rem] py-6 sm:max-w-[30rem]"
        >
            <section
                class="relative w-full rounded-3xl border border-border/80 bg-card/85 p-6 shadow-2xl shadow-indigo-500/5 backdrop-blur-2xl sm:p-9 dark:border-border/60 dark:bg-slate-900/75 dark:shadow-indigo-950/20"
                aria-labelledby="back-office-auth-title"
            >
                <!-- Form Header with Centered Glowing Icon Badge -->
                <div class="mb-7 text-center">
                    <div
                        class="mx-auto mb-4 flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 text-white shadow-lg ring-1 shadow-indigo-500/25 ring-white/20"
                    >
                        <AppLogoIcon class="size-6 text-white" />
                    </div>

                    <div
                        class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-600 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-300"
                    >
                        <ShieldCheck class="size-3.5" />
                        <span>Portal Internal Aparatur</span>
                    </div>

                    <h1
                        v-if="title"
                        id="back-office-auth-title"
                        class="mt-3 text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-2 text-xs leading-relaxed text-muted-foreground sm:text-sm"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </section>
        </main>

        <!-- Footer / Safety Standard Note -->
        <footer
            class="relative z-20 mx-auto flex w-full max-w-xl items-center justify-center py-2 text-center text-xs text-muted-foreground"
        >
            <span
                >Terkoneksi Jalur Aman HTTPS • Akses Internal Aparatur
                Berwenang</span
            >
        </footer>

        <Toaster />
    </div>
</template>
