<script setup lang="ts">
import {
    CheckCircle2,
    CircleDotDashed,
    GitFork,
    MessageSquareText,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { ForwardDispositionReceipt } from '@/types';

defineProps<{ disposition: ForwardDispositionReceipt }>();
</script>

<template>
    <Card
        class="overflow-hidden border-emerald-200/80 bg-gradient-to-br from-emerald-50/70 via-background to-blue-50/50 py-0 shadow-sm dark:border-emerald-950 dark:from-emerald-950/20 dark:via-background dark:to-blue-950/15"
    >
        <CardHeader
            class="border-b border-emerald-100 p-5 sm:p-7 dark:border-emerald-950"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex items-start gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md"
                    >
                        <GitFork class="size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle class="text-xl"
                            >Jalur kerja telah dibuat</CardTitle
                        >
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Branch Asisten ini telah diteruskan menjadi beberapa
                            jalur Kepala Bagian yang berdiri sendiri.
                        </p>
                    </div>
                </div>
                <Badge
                    class="w-fit border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
                >
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    {{ disposition.recipients.length }} branch dibuat
                </Badge>
            </div>
        </CardHeader>

        <CardContent
            class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1.2fr)_minmax(19rem,0.8fr)]"
        >
            <section aria-labelledby="forwarded-recipient-list">
                <div class="flex items-center gap-2">
                    <UsersRound
                        class="size-4 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <h2 id="forwarded-recipient-list" class="font-semibold">
                        Penerima branch
                    </h2>
                </div>

                <ol class="mt-4 grid gap-3 sm:grid-cols-2">
                    <li
                        v-for="recipient in disposition.recipients"
                        :key="recipient.recipient_position.id"
                        class="relative rounded-2xl border bg-background/80 p-4 shadow-xs"
                    >
                        <span
                            class="absolute top-4 -left-px h-7 w-1 rounded-r-full bg-emerald-500"
                            aria-hidden="true"
                        />
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pl-1">
                                <p class="font-semibold">
                                    {{ recipient.recipient_position.name }}
                                </p>
                                <p
                                    class="mt-1 text-sm leading-6 text-muted-foreground"
                                >
                                    {{
                                        recipient.recipient_position.holder_name
                                    }}
                                </p>
                                <p
                                    v-if="
                                        recipient.recipient_position.unit_name
                                    "
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{ recipient.recipient_position.unit_name }}
                                </p>
                            </div>
                            <Badge
                                variant="outline"
                                :class="
                                    dispositionRecipientStatusClass(
                                        recipient.status,
                                    )
                                "
                            >
                                {{
                                    dispositionRecipientStatusLabels[
                                        recipient.status
                                    ]
                                }}
                            </Badge>
                        </div>
                        <p
                            class="mt-4 flex items-center gap-2 text-xs text-muted-foreground tabular-nums"
                        >
                            <CircleDotDashed
                                class="size-3.5"
                                aria-hidden="true"
                            />
                            Diterima
                            {{ formatRoutingDateTime(recipient.received_at) }}
                        </p>
                    </li>
                </ol>
            </section>

            <aside
                class="grid content-start gap-4"
                aria-label="Ringkasan penerusan"
            >
                <div class="rounded-2xl border bg-background/80 p-4 shadow-xs">
                    <p class="text-xs font-medium text-muted-foreground">
                        Instruksi bersama
                    </p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="instruction in disposition.instructions"
                            :key="instruction.code"
                        >
                            <Badge
                                class="border border-blue-200 bg-blue-50 px-3 py-1.5 text-blue-800 hover:bg-blue-50 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200"
                            >
                                {{ instruction.name }}
                            </Badge>
                        </li>
                    </ul>
                    <div
                        v-if="disposition.instruction_note"
                        class="mt-4 flex items-start gap-3 rounded-xl bg-muted/60 p-3"
                    >
                        <MessageSquareText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <p
                            class="text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                        >
                            {{ disposition.instruction_note }}
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl bg-slate-950 p-4 text-slate-100 dark:bg-slate-900"
                >
                    <div class="flex items-start gap-3">
                        <UserRoundCheck
                            class="mt-0.5 size-5 shrink-0 text-emerald-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="font-semibold">Dicatat oleh</p>
                            <p class="mt-1 text-sm text-slate-200">
                                {{ disposition.disposed_by.name }}
                            </p>
                            <p class="mt-1 text-xs leading-5 text-slate-400">
                                {{ disposition.disposed_by.position }}
                                <template v-if="disposition.disposed_by.unit">
                                    · {{ disposition.disposed_by.unit }}
                                </template>
                            </p>
                            <p
                                class="mt-3 text-xs font-medium text-slate-300 tabular-nums"
                            >
                                {{
                                    formatRoutingDateTime(
                                        disposition.disposed_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <p
                    class="flex items-start gap-2 text-xs leading-5 text-muted-foreground"
                >
                    <ShieldCheck
                        class="mt-0.5 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    Catatan ini bersifat read-only dan menjadi bagian dari
                    histori disposisi resmi.
                </p>
            </aside>
        </CardContent>
    </Card>
</template>
