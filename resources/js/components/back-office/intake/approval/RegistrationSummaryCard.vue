<script setup lang="ts">
import { BadgeCheck, Building2, CalendarCheck2, Hash } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { IncomingLetterRegistration } from '@/types';

defineProps<{ registration: IncomingLetterRegistration }>();
</script>

<template>
    <Card class="border-emerald-200 shadow-sm dark:border-emerald-900">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <BadgeCheck
                    class="size-5 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                Registrasi surat masuk
            </CardTitle>
        </CardHeader>
        <CardContent>
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div
                    class="rounded-2xl bg-emerald-50/70 p-4 dark:bg-emerald-950/20"
                >
                    <dt class="flex items-center gap-2 text-muted-foreground">
                        <Hash class="size-4" aria-hidden="true" />
                        Nomor agenda
                    </dt>
                    <dd class="mt-2 font-semibold">
                        {{ registration.agenda_number }} /
                        {{ registration.agenda_year }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-muted/45 p-4">
                    <dt class="flex items-center gap-2 text-muted-foreground">
                        <Building2 class="size-4" aria-hidden="true" />
                        Instansi pengirim
                    </dt>
                    <dd class="mt-2 font-semibold">
                        {{ registration.sender_organization_name }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-muted/45 p-4">
                    <dt class="flex items-center gap-2 text-muted-foreground">
                        <CalendarCheck2 class="size-4" aria-hidden="true" />
                        Diregistrasikan
                    </dt>
                    <dd class="mt-2 font-semibold tabular-nums">
                        {{
                            formatSubmissionDateTime(registration.registered_at)
                        }}
                    </dd>
                </div>
            </dl>
        </CardContent>
    </Card>
</template>
