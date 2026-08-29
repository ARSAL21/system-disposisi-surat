<script setup lang="ts">
import { History, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type {
    OrganizationPosition,
    Paginated,
    PositionAssignment,
} from '@/types';

defineProps<{
    position: OrganizationPosition;
    history: Paginated<PositionAssignment>;
}>();
const emit = defineEmits<{ close: []; page: [page: number] }>();
function date(value: string | null): string {
    return value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Sekarang';
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-violet-200 bg-card shadow-sm dark:border-violet-900"
        aria-labelledby="assignment-history-title"
    >
        <header
            class="flex items-start justify-between gap-4 border-b bg-violet-50/50 p-4 dark:bg-violet-950/20"
        >
            <div class="flex gap-3">
                <span
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                    ><History class="size-5" aria-hidden="true"
                /></span>
                <div>
                    <h2 id="assignment-history-title" class="font-semibold">
                        Riwayat {{ position.name }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ position.code }} · {{ history.meta.total }} periode
                        penugasan
                    </p>
                </div>
            </div>
            <Button
                variant="ghost"
                size="icon"
                class="size-10"
                aria-label="Tutup riwayat"
                @click="emit('close')"
                ><X class="size-4" aria-hidden="true"
            /></Button>
        </header>
        <ol v-if="history.data.length" class="divide-y">
            <li
                v-for="assignment in history.data"
                :key="assignment.id"
                class="grid gap-2 p-4 sm:grid-cols-[minmax(0,1fr)_auto]"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium">{{ assignment.user.name }}</p>
                        <span
                            v-if="assignment.is_active"
                            class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
                            >Aktif</span
                        >
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ assignment.user.email }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Ditugaskan oleh
                        {{ assignment.assigned_by?.name || 'Sistem' }}
                    </p>
                </div>
                <p class="text-sm text-muted-foreground sm:text-right">
                    <span class="block">{{ date(assignment.started_at) }}</span
                    ><span class="block"
                        >hingga {{ date(assignment.ended_at) }}</span
                    >
                </p>
            </li>
        </ol>
        <div v-else class="p-8 text-center text-sm text-muted-foreground">
            Belum ada histori penugasan.
        </div>
        <div
            v-if="history.meta.last_page > 1"
            class="flex items-center justify-end gap-2 border-t p-3"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="history.meta.current_page === 1"
                @click="emit('page', history.meta.current_page - 1)"
                >Sebelumnya</Button
            ><span class="px-2 text-sm"
                >{{ history.meta.current_page }}/{{
                    history.meta.last_page
                }}</span
            ><Button
                variant="outline"
                size="sm"
                :disabled="history.meta.current_page === history.meta.last_page"
                @click="emit('page', history.meta.current_page + 1)"
                >Berikutnya</Button
            >
        </div>
    </section>
</template>
