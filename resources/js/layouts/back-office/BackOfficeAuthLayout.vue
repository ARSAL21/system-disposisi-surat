<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Moon, Sun } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BackOfficeAuthBrandPanel from '@/components/back-office/auth/BackOfficeAuthBrandPanel.vue';
import { Button } from '@/components/ui/button';
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
        class="grid min-h-svh bg-background lg:grid-cols-[minmax(26rem,0.9fr)_1.1fr]"
    >
        <BackOfficeAuthBrandPanel />

        <main
            class="relative flex min-h-svh items-center justify-center px-5 py-20 sm:px-8"
        >
            <div
                class="absolute top-5 left-5 flex items-center gap-3 lg:hidden"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 rounded-lg font-semibold focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span class="max-w-44 truncate text-sm">{{ appName }}</span>
                </Link>
            </div>

            <Button
                variant="ghost"
                size="icon"
                class="absolute top-5 right-5 size-11 rounded-xl"
                :aria-label="
                    resolvedAppearance === 'dark'
                        ? 'Gunakan tema terang'
                        : 'Gunakan tema gelap'
                "
                @click="toggleAppearance"
            >
                <Sun v-if="resolvedAppearance === 'dark'" class="size-5" />
                <Moon v-else class="size-5" />
            </Button>

            <section
                class="w-full max-w-md"
                aria-labelledby="back-office-auth-title"
            >
                <div class="mb-8 space-y-2">
                    <p class="text-sm font-semibold text-primary">
                        Portal Internal
                    </p>
                    <h2
                        v-if="title"
                        id="back-office-auth-title"
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{ title }}
                    </h2>
                    <p
                        v-if="description"
                        class="leading-6 text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>
                <slot />
            </section>
        </main>
    </div>
</template>
