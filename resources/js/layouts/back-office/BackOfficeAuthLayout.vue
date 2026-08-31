<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Moon, ShieldCheck, Sun } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BackOfficeAuthBrandPanel from '@/components/back-office/auth/BackOfficeAuthBrandPanel.vue';
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
        class="relative grid min-h-svh w-full overflow-x-hidden bg-background lg:grid-cols-[1.1fr_0.9fr] 2xl:grid-cols-[1.2fr_0.8fr]"
    >
        <!-- Left Column: Rich Brand Hero & Bento Panel (Desktop) -->
        <BackOfficeAuthBrandPanel />

        <!-- Right Column: Interactive Login Canvas -->
        <main
            class="relative flex min-h-svh flex-col items-center justify-center px-4 py-12 sm:px-8 lg:px-12"
        >
            <!-- Background Ambient Glow in Form Canvas -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute top-10 right-10 size-72 rounded-full bg-indigo-500/10 blur-[90px] dark:bg-indigo-500/15"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute bottom-10 left-10 size-72 rounded-full bg-violet-500/10 blur-[90px] dark:bg-violet-500/15"
            />

            <!-- Top Floating Navigation (Mobile Brand & Global Theme Switcher) -->
            <header
                class="absolute top-4 right-4 left-4 z-20 flex items-center justify-between sm:top-6 sm:right-6 sm:left-6"
            >
                <div class="flex items-center gap-3 lg:hidden">
                    <Link
                        :href="home()"
                        class="flex items-center gap-2.5 rounded-xl p-1 font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-md shadow-indigo-500/20"
                        >
                            <AppLogoIcon class="size-5 text-white" />
                        </span>
                        <span
                            class="max-w-44 truncate text-sm font-bold tracking-tight"
                        >
                            {{ appName }}
                        </span>
                    </Link>
                </div>

                <div class="ml-auto flex items-center gap-2">
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

            <!-- Centered Form Glass Container -->
            <section
                class="relative z-10 w-full max-w-[27rem] rounded-3xl border border-border/70 bg-card/70 p-6 shadow-xl shadow-indigo-500/5 backdrop-blur-xl sm:p-9 dark:bg-card/40 dark:shadow-none"
                aria-labelledby="back-office-auth-title"
            >
                <!-- Form Header -->
                <div class="mb-7 space-y-2 text-left">
                    <div
                        class="inline-flex items-center gap-1.5 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-semibold text-indigo-600 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-300"
                    >
                        <ShieldCheck class="size-3.5" />
                        <span>Portal Internal ASN</span>
                    </div>

                    <h2
                        v-if="title"
                        id="back-office-auth-title"
                        class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="text-xs leading-relaxed text-muted-foreground sm:text-sm"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </section>
        </main>

        <Toaster />
    </div>
</template>
