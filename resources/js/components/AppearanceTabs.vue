<script setup lang="ts">
import { Check, Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const themes = [
    {
        value: 'light',
        label: 'Terang (Light)',
        description:
            'Tampilan bersih, cerah dengan kontras tinggi untuk aktivitas kerja harian.',
        Icon: Sun,
    },
    {
        value: 'dark',
        label: 'Gelap (Dark)',
        description:
            'Tampilan elegan, minim silau untuk kenyamanan mata di lingkungan redup.',
        Icon: Moon,
    },
    {
        value: 'system',
        label: 'Sistem (Auto)',
        description:
            'Menyesuaikan mode secara otomatis mengikuti konfigurasi perangkat Anda.',
        Icon: Monitor,
    },
] as const;
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <button
            v-for="{ value, label, description, Icon } in themes"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'group relative flex flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200 focus:outline-none',
                appearance === value
                    ? 'border-indigo-600 bg-indigo-50/40 shadow-md ring-2 shadow-indigo-500/10 ring-indigo-600/30 dark:border-indigo-500 dark:bg-indigo-950/30 dark:ring-indigo-500/30'
                    : 'border-border/80 bg-white hover:border-indigo-200 hover:bg-neutral-50/60 dark:bg-slate-900 dark:hover:border-indigo-900/60 dark:hover:bg-slate-800/40',
            ]"
        >
            <div class="space-y-4">
                <!-- UI Preview Mockup Box -->
                <div
                    class="relative h-28 w-full overflow-hidden rounded-xl border p-2.5 transition-transform duration-200 group-hover:scale-[1.02]"
                    :class="[
                        value === 'light'
                            ? 'border-neutral-200 bg-slate-100'
                            : value === 'dark'
                              ? 'border-slate-800 bg-slate-950'
                              : 'border-neutral-300 bg-gradient-to-r from-slate-100 via-slate-200 to-slate-950',
                    ]"
                >
                    <!-- Mockup content for Light -->
                    <div
                        v-if="value === 'light'"
                        class="flex h-full flex-col gap-1.5 rounded-lg bg-white p-2 shadow-xs"
                    >
                        <div class="flex items-center gap-1.5">
                            <div class="size-2 rounded-full bg-indigo-500" />
                            <div
                                class="h-1.5 w-12 rounded-full bg-neutral-200"
                            />
                            <div
                                class="ml-auto h-1.5 w-4 rounded-full bg-neutral-200"
                            />
                        </div>
                        <div class="grid grid-cols-3 gap-1 pt-1">
                            <div class="h-10 rounded-md bg-neutral-100 p-1">
                                <div
                                    class="h-1.5 w-6 rounded-full bg-indigo-300"
                                />
                            </div>
                            <div class="h-10 rounded-md bg-neutral-100 p-1">
                                <div
                                    class="h-1.5 w-5 rounded-full bg-neutral-300"
                                />
                            </div>
                            <div class="h-10 rounded-md bg-neutral-100 p-1">
                                <div
                                    class="h-1.5 w-7 rounded-full bg-neutral-300"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mockup content for Dark -->
                    <div
                        v-else-if="value === 'dark'"
                        class="flex h-full flex-col gap-1.5 rounded-lg border border-slate-800 bg-slate-900 p-2 shadow-xs"
                    >
                        <div class="flex items-center gap-1.5">
                            <div class="size-2 rounded-full bg-indigo-400" />
                            <div class="h-1.5 w-12 rounded-full bg-slate-700" />
                            <div
                                class="ml-auto h-1.5 w-4 rounded-full bg-slate-700"
                            />
                        </div>
                        <div class="grid grid-cols-3 gap-1 pt-1">
                            <div
                                class="h-10 rounded-md border border-slate-700/50 bg-slate-800/80 p-1"
                            >
                                <div
                                    class="h-1.5 w-6 rounded-full bg-indigo-400"
                                />
                            </div>
                            <div
                                class="h-10 rounded-md border border-slate-700/50 bg-slate-800/80 p-1"
                            >
                                <div
                                    class="h-1.5 w-5 rounded-full bg-slate-600"
                                />
                            </div>
                            <div
                                class="h-10 rounded-md border border-slate-700/50 bg-slate-800/80 p-1"
                            >
                                <div
                                    class="h-1.5 w-7 rounded-full bg-slate-600"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mockup content for System -->
                    <div
                        v-else
                        class="flex h-full overflow-hidden rounded-lg shadow-xs"
                    >
                        <div
                            class="flex h-full w-1/2 flex-col gap-1 border-r border-neutral-200 bg-white p-2"
                        >
                            <div class="size-2 rounded-full bg-indigo-500" />
                            <div
                                class="h-1.5 w-8 rounded-full bg-neutral-200"
                            />
                            <div class="mt-1 h-6 rounded-md bg-neutral-100" />
                        </div>
                        <div
                            class="flex h-full w-1/2 flex-col gap-1 bg-slate-900 p-2"
                        >
                            <div class="size-2 rounded-full bg-indigo-400" />
                            <div class="h-1.5 w-8 rounded-full bg-slate-700" />
                            <div class="mt-1 h-6 rounded-md bg-slate-800" />
                        </div>
                    </div>
                </div>

                <!-- Title & Meta -->
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div
                                :class="[
                                    'flex size-7 items-center justify-center rounded-lg transition-colors',
                                    appearance === value
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-neutral-100 text-neutral-600 dark:bg-slate-800 dark:text-neutral-300',
                                ]"
                            >
                                <component :is="Icon" class="size-4" />
                            </div>
                            <span class="text-sm font-semibold text-foreground">
                                {{ label }}
                            </span>
                        </div>

                        <!-- Active Radio Pill with Checkmark -->
                        <div
                            :class="[
                                'flex size-5 items-center justify-center rounded-full border transition-all',
                                appearance === value
                                    ? 'border-indigo-600 bg-indigo-600 text-white dark:border-indigo-500 dark:bg-indigo-500'
                                    : 'border-muted-foreground/30 bg-transparent',
                            ]"
                        >
                            <Check
                                v-if="appearance === value"
                                class="size-3 stroke-[3]"
                            />
                        </div>
                    </div>
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>
        </button>
    </div>
</template>
