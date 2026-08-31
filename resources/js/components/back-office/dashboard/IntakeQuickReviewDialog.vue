<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    Building2,
    ExternalLink,
    FileSearch,
    FileText,
    Mail,
    Phone,
    User,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatBytes } from '@/lib/documentVersionPreview';
import {
    formatSubmissionDate,
    getSubmissionStatusMeta,
} from '@/lib/intakeDashboardPreview';
import type { IntakeQueueItem } from '@/types';

defineProps<{
    open: boolean;
    submission: IntakeQueueItem | null;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="submission" class="sm:max-w-2xl">
            <DialogHeader>
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        class="gap-1.5 px-2.5 py-0.5 text-xs font-semibold"
                        :class="
                            getSubmissionStatusMeta(submission.status)
                                .badgeClass
                        "
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="
                                getSubmissionStatusMeta(submission.status)
                                    .dotClass
                            "
                            aria-hidden="true"
                        />
                        <span>{{
                            getSubmissionStatusMeta(submission.status).label
                        }}</span>
                    </Badge>

                    <Badge variant="secondary" class="font-mono text-[11px]">
                        ID: {{ submission.public_id.substring(0, 10) }}...
                    </Badge>

                    <Badge variant="outline" class="text-[11px]">
                        {{
                            submission.source === 'ONLINE'
                                ? 'Pengajuan Online'
                                : 'Loket Fisik/Manual'
                        }}
                    </Badge>
                </div>

                <DialogTitle
                    class="mt-2 text-lg font-bold tracking-tight text-foreground sm:text-xl"
                >
                    {{ submission.subject }}
                </DialogTitle>

                <DialogDescription class="text-xs text-muted-foreground">
                    Diterima pada
                    {{ formatSubmissionDate(submission.submitted_at) }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <!-- High Priority Kabag Correction Notice (if INTERNAL_REVISION_REQUIRED) -->
                <div
                    v-if="
                        submission.status === 'INTERNAL_REVISION_REQUIRED' &&
                        submission.internal_revision_note
                    "
                    class="rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-xs text-rose-950 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-200"
                >
                    <div class="flex items-start gap-2.5">
                        <AlertCircle
                            class="mt-0.5 size-4 shrink-0 text-rose-600 dark:text-rose-400"
                            aria-hidden="true"
                        />
                        <div class="space-y-1">
                            <span
                                class="font-bold text-rose-800 dark:text-rose-300"
                            >
                                Catatan Pengembalian dari Kepala Bagian Umum:
                            </span>
                            <p class="leading-relaxed whitespace-pre-wrap">
                                {{ submission.internal_revision_note }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section: Sender & Contact Information -->
                <div
                    class="space-y-3 rounded-xl border border-border/80 bg-muted/30 p-4"
                >
                    <div
                        class="flex items-center gap-1.5 text-xs font-bold text-foreground"
                    >
                        <Building2
                            class="size-4 text-primary"
                            aria-hidden="true"
                        />
                        <span>Identitas Pengirim & Kontak</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                        <div>
                            <span class="text-muted-foreground"
                                >Instansi / Lembaga:</span
                            >
                            <p class="mt-0.5 font-semibold text-foreground">
                                {{ submission.sender_organization_name }}
                            </p>
                        </div>

                        <div>
                            <span class="text-muted-foreground"
                                >Penanggung Jawab / Kontak:</span
                            >
                            <p
                                class="mt-0.5 flex items-center gap-1.5 font-semibold text-foreground"
                            >
                                <User
                                    class="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                {{ submission.contact_name }}
                            </p>
                        </div>

                        <div v-if="submission.contact_email">
                            <span class="text-muted-foreground">Email:</span>
                            <p
                                class="mt-0.5 flex items-center gap-1.5 font-medium text-foreground"
                            >
                                <Mail
                                    class="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                {{ submission.contact_email }}
                            </p>
                        </div>

                        <div v-if="submission.contact_phone">
                            <span class="text-muted-foreground"
                                >Telepon / WhatsApp:</span
                            >
                            <p
                                class="mt-0.5 flex items-center gap-1.5 font-medium text-foreground"
                            >
                                <Phone
                                    class="size-3.5 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                {{ submission.contact_phone }}
                            </p>
                        </div>

                        <div v-if="submission.external_letter_number">
                            <span class="text-muted-foreground"
                                >Nomor Surat Pengirim:</span
                            >
                            <p class="mt-0.5 font-mono text-foreground">
                                {{ submission.external_letter_number }}
                                <span
                                    v-if="submission.external_letter_date"
                                    class="text-[11px] text-muted-foreground"
                                >
                                    ({{ submission.external_letter_date }})
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Section: Summary & Letter Content -->
                <div
                    v-if="submission.summary"
                    class="space-y-1.5 rounded-xl border border-border/80 bg-muted/20 p-4 text-xs"
                >
                    <span class="font-semibold text-foreground"
                        >Ringkasan Pengajuan:</span
                    >
                    <p
                        class="leading-relaxed whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ submission.summary }}
                    </p>
                </div>

                <!-- Section: Document Attachment -->
                <div
                    v-if="submission.document"
                    class="flex items-center justify-between rounded-xl border border-border bg-card p-3.5"
                >
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div
                            class="shrink-0 rounded-lg bg-primary/10 p-2 text-primary"
                        >
                            <FileText class="size-5" aria-hidden="true" />
                        </div>
                        <div class="truncate text-xs">
                            <p class="truncate font-semibold text-foreground">
                                {{ submission.document.original_filename }}
                            </p>
                            <p class="text-[11px] text-muted-foreground">
                                {{
                                    formatBytes(submission.document.size_bytes)
                                }}
                                • {{ submission.document.mime_type }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="submission.links.document_preview"
                        class="shrink-0 pl-2"
                    >
                        <Button
                            as="a"
                            :href="submission.links.document_preview"
                            target="_blank"
                            rel="noopener noreferrer"
                            variant="outline"
                            size="sm"
                            class="h-8 cursor-pointer gap-1.5 text-xs"
                        >
                            <ExternalLink class="size-3.5" aria-hidden="true" />
                            <span>Pratinjau PDF</span>
                        </Button>
                    </div>
                </div>
            </div>

            <DialogFooter
                class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-4"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-10 cursor-pointer text-xs"
                    @click="emit('update:open', false)"
                >
                    Tutup
                </Button>

                <Button
                    as-child
                    class="min-h-10 gap-1.5 bg-primary text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                >
                    <Link :href="submission.links.show">
                        <FileSearch class="size-4" aria-hidden="true" />
                        <span>Buka Halaman Screening Penuh</span>
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </Link>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
