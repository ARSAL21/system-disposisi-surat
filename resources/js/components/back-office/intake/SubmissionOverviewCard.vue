<script setup lang="ts">
import { CalendarDays, FileBadge2, Landmark, Text } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatSubmissionDate } from '@/lib/submissionPresentation';
import type { IntakeSubmission } from '@/types';

type SubmissionOverview = Pick<
    IntakeSubmission,
    | 'external_letter_number'
    | 'external_letter_date'
    | 'sender_organization_name'
    | 'summary'
>;

defineProps<{ submission: SubmissionOverview }>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <CardTitle>Informasi surat</CardTitle>
        </CardHeader>
        <CardContent>
            <dl class="grid gap-5 sm:grid-cols-2">
                <div class="flex gap-3">
                    <FileBadge2
                        class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">
                            Nomor surat
                        </dt>
                        <dd class="mt-1 text-sm font-semibold">
                            {{
                                submission.external_letter_number ??
                                'Belum dicantumkan'
                            }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CalendarDays
                        class="mt-0.5 size-5 shrink-0 text-violet-600 dark:text-violet-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">
                            Tanggal surat
                        </dt>
                        <dd class="mt-1 text-sm font-semibold tabular-nums">
                            {{
                                formatSubmissionDate(
                                    submission.external_letter_date,
                                )
                            }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3 sm:col-span-2">
                    <Landmark
                        class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">
                            Instansi pengirim
                        </dt>
                        <dd class="mt-1 text-sm font-semibold">
                            {{ submission.sender_organization_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3 sm:col-span-2">
                    <Text
                        class="mt-0.5 size-5 shrink-0 text-violet-600 dark:text-violet-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">
                            Ringkasan pengirim
                        </dt>
                        <dd class="mt-1 text-sm leading-6">
                            {{
                                submission.summary ??
                                'Tidak ada ringkasan tambahan.'
                            }}
                        </dd>
                    </div>
                </div>
            </dl>
        </CardContent>
    </Card>
</template>
