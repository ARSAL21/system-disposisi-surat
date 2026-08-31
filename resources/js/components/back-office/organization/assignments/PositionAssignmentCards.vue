<script setup lang="ts">
import { BriefcaseBusiness, History } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationPosition } from '@/types';

defineProps<{ positions: OrganizationPosition[]; canMutate: boolean }>();
const emit = defineEmits<{
    manage: [position: OrganizationPosition];
    history: [position: OrganizationPosition];
}>();
function status(position: OrganizationPosition): string {
    if (!position.is_active) {
        return 'Nonaktif';
    }

    return position.active_assignment ? 'Terisi' : 'Lowong';
}
</script>

<template>
    <div class="divide-y md:hidden">
        <article v-for="position in positions" :key="position.id" class="p-4">
            <div class="flex items-start gap-3">
                <span
                    class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300"
                    ><BriefcaseBusiness class="size-5" aria-hidden="true"
                /></span>
                <div class="min-w-0">
                    <p class="font-medium">{{ position.name }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ position.level.name }} ·
                        {{ position.unit?.name || 'Lintas unit' }}
                    </p>
                </div>
                <Badge class="ml-auto" variant="secondary">{{
                    status(position)
                }}</Badge>
            </div>
            <div class="mt-3 rounded-xl bg-muted/50 p-3 text-sm">
                <p v-if="position.active_assignment">
                    {{ position.active_assignment.user.name }}
                </p>
                <p v-else class="text-muted-foreground">
                    Belum ada pejabat aktif
                </p>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <Button
                    variant="outline"
                    class="min-h-11"
                    @click="emit('history', position)"
                    ><History class="size-4" aria-hidden="true" />
                    Riwayat</Button
                >
                <Button
                    class="min-h-11 bg-violet-700 hover:bg-violet-800"
                    :disabled="!canMutate || !position.is_active"
                    @click="emit('manage', position)"
                    >{{
                        position.active_assignment
                            ? 'Ganti pejabat'
                            : 'Tugaskan'
                    }}</Button
                >
            </div>
        </article>
    </div>
</template>
