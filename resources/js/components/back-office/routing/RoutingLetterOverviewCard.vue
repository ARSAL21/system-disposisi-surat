<script setup lang="ts">
import { Building2, CalendarClock, FileDigit, Mail } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{ letter: LetterRoutingItem }>();

const fields = [
    {
        key: 'agenda_number' as const,
        label: 'Nomor agenda',
        icon: FileDigit,
    },
    {
        key: 'external_letter_number' as const,
        label: 'Nomor surat pengirim',
        icon: Mail,
    },
    {
        key: 'sender_organization_name' as const,
        label: 'Instansi pengirim',
        icon: Building2,
    },
];
</script>

<template>
    <Card class="py-0 shadow-sm">
        <CardHeader class="border-b p-5 sm:p-6">
            <CardTitle>Ringkasan surat resmi</CardTitle>
            <p class="text-sm leading-6 text-muted-foreground">
                Cocokkan identitas surat dan dokumen sebelum melanjutkan alur.
            </p>
        </CardHeader>
        <CardContent class="p-5 sm:p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div
                    v-for="field in fields"
                    :key="field.key"
                    class="flex gap-3 rounded-2xl bg-muted/50 p-4"
                >
                    <component
                        :is="field.icon"
                        class="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">
                            {{ field.label }}
                        </dt>
                        <dd class="mt-1 font-semibold break-words">
                            {{ letter[field.key] || '-' }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3 rounded-2xl bg-muted/50 p-4">
                    <CalendarClock
                        class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Waktu diterima
                        </dt>
                        <dd class="mt-1 font-semibold tabular-nums">
                            {{ formatRoutingDateTime(letter.received_at) }}
                        </dd>
                    </div>
                </div>
            </dl>

            <div class="mt-4 rounded-2xl border p-4 sm:p-5">
                <p class="text-xs font-semibold text-muted-foreground">
                    Perihal
                </p>
                <p class="mt-2 leading-7 font-medium">{{ letter.subject }}</p>
            </div>
        </CardContent>
    </Card>
</template>
