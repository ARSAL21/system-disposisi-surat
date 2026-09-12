<script setup lang="ts">
import { Check, Copy, Clock3, UsersRound } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OutgoingLetterDetail } from '@/types';

const props = defineProps<{ letter: OutgoingLetterDetail }>();
const copies = computed(
    () =>
        props.letter.internal_copies ??
        props.letter.standalone_draft?.copy_recipients?.map((position) => ({
            name: position,
            position,
            notified_at: null,
            acknowledged: false,
        })) ??
        [],
);
</script>

<template>
    <Card v-if="copies.length" class="overflow-hidden">
        <CardHeader class="border-b bg-muted/20 pb-4"
            ><CardTitle class="flex items-center gap-2 text-base"
                ><Copy class="size-4 text-indigo-600" /> Tembusan internal
                <Badge variant="outline" class="ml-auto rounded-full">{{
                    copies.length
                }}</Badge></CardTitle
            ></CardHeader
        >
        <CardContent class="space-y-3 p-5">
            <Alert
                class="border-sky-200 bg-sky-50/60 dark:border-sky-900 dark:bg-sky-950/25"
                ><UsersRound class="size-4 text-sky-600" /><AlertTitle
                    >Untuk diketahui internal</AlertTitle
                ><AlertDescription
                    >Tembusan hanya terlihat oleh pejabat yang berwenang dan
                    tidak membuat tautan publik.</AlertDescription
                ></Alert
            >
            <ul class="space-y-2" aria-label="Daftar tembusan internal">
                <li
                    v-for="copy in copies"
                    :key="`${copy.position}-${copy.name}`"
                    class="flex items-center justify-between gap-3 rounded-2xl border p-3"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            {{ copy.name }}
                        </p>
                        <p
                            class="mt-0.5 truncate text-xs text-muted-foreground"
                        >
                            {{ copy.position }}
                        </p>
                    </div>
                    <Badge
                        v-if="copy.acknowledged"
                        class="shrink-0 rounded-full border-emerald-200 bg-emerald-50 text-emerald-700"
                        ><Check class="size-3.5" /> Diketahui</Badge
                    ><Badge
                        v-else-if="copy.notified_at"
                        variant="outline"
                        class="shrink-0 rounded-full"
                        ><Clock3 class="size-3.5" /> Terkirim</Badge
                    ><Badge
                        v-else
                        variant="outline"
                        class="shrink-0 rounded-full"
                        >Menunggu</Badge
                    >
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
