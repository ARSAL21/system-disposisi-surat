<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Clock,
    FileText,
    History,
    Plus,
    ShieldAlert,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { DocumentVersionLetter } from '@/types';

defineProps<{
    letter: DocumentVersionLetter;
    totalVersions: number;
    currentVersionNumber: number;
    canCreateVersion: boolean;
    preview?: boolean;
}>();

const emit = defineEmits<{
    openCreate: [];
}>();
</script>

<template>
    <header
        class="flex flex-col gap-4 border-b border-border/80 pb-5 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    href="/back-office/documents"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Kembali ke Arsip Dokumen</span>
                </Link>
                <span class="text-xs text-muted-foreground/60">•</span>
                <Badge
                    variant="outline"
                    class="gap-1 px-2 py-0.5 text-xs font-medium"
                >
                    <FileText class="size-3 text-primary" aria-hidden="true" />
                    <span>Agenda {{ letter.agenda_number }}</span>
                </Badge>
                <Badge
                    v-if="preview"
                    variant="secondary"
                    class="border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                >
                    <ShieldAlert class="size-3" aria-hidden="true" />
                    <span>Mode pratinjau</span>
                </Badge>
            </div>

            <h1
                class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
            >
                Histori Dokumen Resmi
            </h1>

            <p class="max-w-3xl text-sm leading-relaxed text-muted-foreground">
                {{ letter.subject }}
            </p>

            <div
                class="flex flex-wrap items-center gap-2 pt-1 text-xs text-muted-foreground"
            >
                <span
                    class="inline-flex items-center gap-1 font-medium text-foreground"
                >
                    <History class="size-3.5 text-primary" aria-hidden="true" />
                    {{ totalVersions }} Versi Tercatat
                </span>
                <span>•</span>
                <span class="inline-flex items-center gap-1">
                    <Clock
                        class="size-3.5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    Versi Aktif:
                    <strong class="text-foreground"
                        >v{{ currentVersionNumber }}</strong
                    >
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2 pt-2 sm:pt-0">
            <Button
                v-if="canCreateVersion"
                type="button"
                class="min-h-11 w-full gap-1.5 bg-primary font-medium text-primary-foreground shadow hover:bg-primary/90 sm:w-auto"
                @click="emit('openCreate')"
            >
                <Plus class="size-4" aria-hidden="true" />
                <span>Unggah Versi Koreksi</span>
            </Button>
        </div>
    </header>
</template>
