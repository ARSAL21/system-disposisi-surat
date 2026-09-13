<script setup lang="ts">
import { BookOpenCheck, Crown, GitBranch, Landmark, UserCheck, UserX } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { OrganizationTreePosition } from '@/types';

defineProps<{
    positions: OrganizationTreePosition[];
}>();

const emit = defineEmits<{
    inspectPosition: [position: OrganizationTreePosition];
}>();
</script>

<template>
    <section class="w-full max-w-5xl rounded-3xl border border-amber-200/80 bg-gradient-to-br from-amber-50/80 via-background to-indigo-50/45 p-5 shadow-sm dark:border-amber-900/50 dark:from-amber-950/25 dark:via-background dark:to-indigo-950/20 sm:p-6" aria-labelledby="expert-advisor-relationship-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-700 dark:text-amber-300"><Crown class="size-5" aria-hidden="true" /></span>
                <div><div class="flex flex-wrap items-center gap-2"><h2 id="expert-advisor-relationship-title" class="font-semibold">Staf Ahli Wali Kota</h2><Badge variant="outline" class="border-amber-300/80 text-[10px] text-amber-800 dark:border-amber-800 dark:text-amber-200">Penasihat</Badge></div><p class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground">Memberi pertimbangan strategis langsung kepada Wali Kota. Tidak meneruskan disposisi ke Asisten atau Kepala Bagian.</p></div>
            </div>
            <div class="flex items-center gap-2 rounded-2xl border border-indigo-200/70 bg-background/75 px-3 py-2 text-xs text-muted-foreground dark:border-indigo-900/50"><Landmark class="size-4 text-indigo-600 dark:text-indigo-300" aria-hidden="true" /> Koordinasi administratif: Sekda</div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)_210px] lg:items-center">
            <div class="rounded-2xl border border-amber-200/70 bg-background/80 p-4 text-center dark:border-amber-900/50"><Crown class="mx-auto size-5 text-amber-600 dark:text-amber-300" aria-hidden="true" /><p class="mt-2 text-xs font-semibold">Wali Kota</p><p class="mt-1 text-[11px] text-muted-foreground">Pertanggungjawaban langsung</p></div>
            <div class="relative hidden items-center justify-center lg:flex" aria-hidden="true"><div class="h-px w-full bg-gradient-to-r from-amber-400 via-indigo-400 to-indigo-400" /><span class="absolute left-1/2 flex size-7 -translate-x-1/2 items-center justify-center rounded-full border bg-background text-indigo-600 shadow-sm"><GitBranch class="size-3.5" /></span></div>
            <div class="space-y-2 rounded-2xl border border-indigo-200/70 bg-background/80 p-4 dark:border-indigo-900/50"><div class="flex items-center gap-2 text-xs font-semibold"><Landmark class="size-4 text-indigo-600 dark:text-indigo-300" aria-hidden="true" /> Sekda</div><p class="text-[11px] leading-5 text-muted-foreground">Mengatur dukungan dan koordinasi administratif, bukan isi rekomendasi.</p></div>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <button v-for="position in positions" :key="position.id" type="button" class="group rounded-2xl border border-border/80 bg-background p-4 text-left transition-all hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm dark:hover:border-amber-800" @click="emit('inspectPosition', position)">
                <div class="flex items-start justify-between gap-2"><div class="flex size-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300"><BookOpenCheck class="size-4" aria-hidden="true" /></div><Badge :variant="position.is_active ? 'outline' : 'secondary'" class="text-[10px]">{{ position.is_active ? 'Aktif' : 'Nonaktif' }}</Badge></div>
                <p class="mt-3 line-clamp-2 text-sm font-semibold group-hover:text-amber-700 dark:group-hover:text-amber-300">{{ position.name }}</p>
                <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"><UserCheck v-if="position.active_assignment" class="size-3" aria-hidden="true" /><UserX v-else class="size-3" aria-hidden="true" />{{ position.active_assignment?.user.name || 'Belum ada pemegang' }}</p>
            </button>
        </div>
    </section>
</template>

