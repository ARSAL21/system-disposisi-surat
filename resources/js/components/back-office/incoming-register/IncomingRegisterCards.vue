<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarClock,
    FileText,
    Hash,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatIncomingRegisterBytes,
    formatIncomingRegisterDateTime,
    incomingRegisterSourceClass,
    incomingRegisterSourceLabels,
    incomingRegisterStatusClass,
    incomingRegisterStatusLabels,
} from '@/lib/incomingRegisterPresentation';
import type { IncomingRegisterItem } from '@/types';

defineProps<{ letters: IncomingRegisterItem[] }>();
</script>

<template>
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="letter in letters"
            :key="letter.id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="incomingRegisterSourceClass(letter.source)"
                >
                    {{ incomingRegisterSourceLabels[letter.source] }}
                </Badge>
                <Badge
                    variant="outline"
                    :class="incomingRegisterStatusClass(letter.status)"
                >
                    {{ incomingRegisterStatusLabels[letter.status] }}
                </Badge>
            </div>

            <div class="mt-4 flex items-start gap-3">
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                >
                    <Hash class="size-4" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <p class="font-mono text-xs font-semibold">
                        {{ letter.agenda_number }}
                    </p>
                    <h2 class="mt-2 leading-6 font-semibold">
                        {{ letter.subject }}
                    </h2>
                </div>
            </div>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div
                    class="flex gap-3 rounded-xl bg-muted/40 p-3 sm:col-span-2"
                >
                    <Building2
                        class="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Pengirim</dt>
                        <dd class="mt-1 font-medium">
                            {{ letter.sender_organization_name }}
                        </dd>
                        <dd class="mt-0.5 text-xs text-muted-foreground">
                            Kontak: {{ letter.contact_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CalendarClock
                        class="mt-0.5 size-4 shrink-0 text-amber-700 dark:text-amber-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Diterima</dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{
                                formatIncomingRegisterDateTime(
                                    letter.received_at,
                                )
                            }}
                        </dd>
                    </div>
                </div>
                <div v-if="letter.document" class="flex min-w-0 gap-3">
                    <FileText
                        class="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">
                            Scan resmi
                        </dt>
                        <dd class="mt-1 truncate font-medium">
                            {{ letter.document.original_filename }}
                        </dd>
                        <dd class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                formatIncomingRegisterBytes(
                                    letter.document.size_bytes,
                                )
                            }}
                        </dd>
                    </div>
                </div>
                <div v-else class="text-xs text-muted-foreground">
                    Metadata scan tidak tersedia
                </div>
            </dl>

            <div
                class="mt-4 rounded-xl border border-dashed px-3 py-2.5 text-xs"
            >
                <span class="text-muted-foreground">Nomor surat pengirim</span>
                <p class="mt-1 font-medium">
                    {{ letter.external_letter_number || 'Tidak tercantum' }}
                </p>
            </div>

            <Button
                v-if="letter.links.document_history"
                as-child
                variant="outline"
                class="mt-4 min-h-11 w-full rounded-xl"
            >
                <Link :href="letter.links.document_history">
                    Lihat dokumen dan histori
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <p
                v-else
                class="mt-4 rounded-xl bg-muted/40 px-3 py-3 text-center text-xs text-muted-foreground"
            >
                Akses dokumen tidak tersedia untuk akun ini.
            </p>
        </article>
    </div>
</template>
