<script setup lang="ts">
import { CheckCircle2, ClipboardCheck } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import type { ScreeningChecklistItem } from '@/types';

const props = defineProps<{ items: ScreeningChecklistItem[] }>();
const emit = defineEmits<{ toggle: [id: string, checked: boolean] }>();

const completedCount = computed(
    () => props.items.filter((item) => item.checked).length,
);
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <CardTitle>Daftar pemeriksaan kelengkapan</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pemeriksaan teknis oleh petugas Bagian Umum.
                    </p>
                </div>
                <span
                    class="rounded-full bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-800 dark:text-blue-300"
                >
                    {{ completedCount }}/{{ items.length }} selesai
                </span>
            </div>
        </CardHeader>
        <CardContent class="space-y-3">
            <div
                v-for="item in items"
                :key="item.id"
                :class="[
                    'flex gap-3 rounded-2xl border p-4 transition-colors motion-reduce:transition-none',
                    item.checked
                        ? 'border-blue-200 bg-blue-50/65 dark:border-blue-900 dark:bg-blue-950/20'
                        : 'bg-background',
                ]"
            >
                <Checkbox
                    :id="`check-${item.id}`"
                    :model-value="item.checked"
                    class="mt-0.5 size-5"
                    @update:model-value="
                        emit('toggle', item.id, Boolean($event))
                    "
                />
                <label :for="`check-${item.id}`" class="min-w-0 cursor-pointer">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <CheckCircle2
                            v-if="item.checked"
                            class="size-4 text-blue-700 dark:text-blue-300"
                            aria-hidden="true"
                        />
                        <ClipboardCheck
                            v-else
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </span>
                    <span
                        class="mt-1 block text-xs leading-5 text-muted-foreground"
                        >{{ item.description }}</span
                    >
                </label>
            </div>
        </CardContent>
    </Card>
</template>
