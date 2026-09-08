<script setup lang="ts">
import { Check, Clock3, History } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OutgoingLetterHistoryEntry } from '@/types';

defineProps<{ entries: OutgoingLetterHistoryEntry[] }>();

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <Card>
        <CardHeader class="pb-3"><CardTitle class="flex items-center gap-2 text-base"><History class="size-4 text-indigo-600" /> Jejak penerbitan</CardTitle></CardHeader>
        <CardContent>
            <ol class="space-y-0">
                <li v-for="(entry, index) in entries" :key="entry.key" class="relative flex gap-3 pb-5 last:pb-0">
                    <span v-if="index < entries.length - 1" class="absolute top-8 bottom-0 left-4 w-px bg-border" aria-hidden="true" />
                    <span class="relative z-10 grid size-8 shrink-0 place-items-center rounded-full border" :class="entry.state === 'DONE' ? 'border-emerald-500 bg-emerald-500 text-white' : entry.state === 'STOPPED' ? 'border-slate-400 bg-slate-300 text-slate-700' : 'border-indigo-500 bg-background text-indigo-600 ring-4 ring-indigo-500/10'"><Check v-if="entry.state === 'DONE'" class="size-3.5" /><Clock3 v-else class="size-3.5" /></span>
                    <div class="min-w-0 pt-1"><div class="flex flex-wrap items-center gap-x-2 gap-y-1"><p class="text-sm font-semibold">{{ entry.label }}</p><time class="text-xs text-muted-foreground">{{ formatDate(entry.occurred_at) }}</time></div><p class="mt-1 text-sm leading-6 text-muted-foreground">{{ entry.description }}</p><p class="mt-2 text-xs font-medium">{{ entry.actor_name }} · {{ entry.actor_position }}</p></div>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
