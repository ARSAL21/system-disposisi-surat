<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FileText, Moon, Sun } from '@lucide/vue';
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
        class="relative flex min-h-screen w-full flex-col justify-between bg-background p-4 sm:p-6"
    >
        <!-- Ambient Luminous Mesh (Teal / Emerald / Cyan) -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-gradient-to-b from-teal-500/15 via-emerald-600/10 to-transparent blur-[130px] dark:from-teal-600/20 dark:via-emerald-700/15"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -bottom-32 left-1/2 size-[32rem] -translate-x-1/2 rounded-full bg-gradient-to-t from-cyan-500/15 via-teal-600/10 to-transparent blur-[130px] dark:from-cyan-600/15 dark:via-teal-600/10"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(20,184,166,0.05),transparent_65%)]"
        />

        <!-- Subtle Grid Pattern Overlay -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 [background-image:linear-gradient(to_right,#000000_1px,transparent_1px),linear-gradient(to_bottom,#000000_1px,transparent_1px)] [background-size:36px_36px] opacity-[0.02] dark:[background-image:linear-gradient(to_right,#ffffff_1px,transparent_1px),linear-gradient(to_bottom,#ffffff_1px,transparent_1px)] dark:opacity-[0.035]"
        />

        <!-- Minimal Top Header Navigation (No Laravel logo) -->
        <header
            class="relative z-20 mx-auto flex w-full max-w-xl items-center justify-between py-1"
        >
            <Link
                :href="home.url()"
                class="group flex items-center gap-2.5 rounded-xl p-1 font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span
                    class="flex size-8.5 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 text-white shadow-md shadow-emerald-500/20 transition-transform duration-300 group-hover:scale-105"
                >
                    <FileText class="size-4 text-white" />
                </span>
                <span
                    class="text-sm font-bold tracking-tight text-foreground transition-colors group-hover:text-emerald-600 dark:group-hover:text-emerald-400"
                >
                    {{ appName }}
                </span>
            </Link>

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="icon"
                    class="size-8.5 rounded-xl border-border/80 bg-card/70 backdrop-blur-md transition-transform hover:scale-105"
                    :aria-label="
                        resolvedAppearance === 'dark'
                            ? 'Gunakan tema terang'
                            : 'Gunakan tema gelap'
                    "
                    @click="toggleAppearance"
                >
                    <Sun
                        v-if="resolvedAppearance === 'dark'"
                        class="size-4 text-amber-400"
                    />
                    <Moon v-else class="size-4 text-emerald-600" />
                </Button>
            </div>
        </header>

        <!-- Main Single Center Section / Elevated Glass Card -->
        <main
            class="relative z-10 mx-auto my-auto w-full max-w-[28rem] py-3 sm:max-w-[29.5rem]"
        >
            <section
                class="relative w-full rounded-3xl border border-border/80 bg-card/90 p-5 shadow-2xl shadow-emerald-500/5 backdrop-blur-2xl sm:p-7 dark:border-border/60 dark:bg-slate-900/80 dark:shadow-emerald-950/20"
                aria-labelledby="auth-card-title"
            >
                <!-- Optional Header for generic sub-pages (e.g. ForgotPassword) -->
                <div
                    v-if="title"
                    class="mb-6 text-center"
                >
                    <div
                        class="mx-auto mb-3 flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 text-white shadow-lg ring-1 shadow-emerald-500/25 ring-white/20"
                    >
                        <FileText class="size-5.5 text-white" />
                    </div>

                    <h1
                        id="auth-card-title"
                        class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="mt-1 text-xs leading-relaxed text-muted-foreground sm:text-sm"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </section>
        </main>

        <!-- Minimal Clean Footer -->
        <footer
            class="relative z-20 mx-auto flex w-full max-w-xl items-center justify-center py-2 text-center text-xs text-muted-foreground"
        >
            <span>Layanan Pengajuan & Pelacakan Surat Publik</span>
        </footer>

        <Toaster />
    </div>
</template>
