<script setup lang="ts">
import { ArrowRightLeft, History, UserPlus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationPosition } from '@/types';

defineProps<{ positions: OrganizationPosition[]; canMutate: boolean }>();
const emit = defineEmits<{
    manage: [position: OrganizationPosition];
    history: [position: OrganizationPosition];
}>();
function status(position: OrganizationPosition): string {
    if (!position.is_active) return 'Nonaktif';
    return position.active_assignment ? 'Terisi' : 'Lowong';
}
</script>

<template>
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full text-left text-sm">
            <thead
                class="bg-muted/50 text-xs tracking-wide text-muted-foreground uppercase"
            >
                <tr>
                    <th class="px-5 py-3 font-medium">Jabatan</th>
                    <th class="px-5 py-3 font-medium">Level / unit</th>
                    <th class="px-5 py-3 font-medium">Pejabat aktif</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="position in positions"
                    :key="position.id"
                    class="hover:bg-muted/30"
                >
                    <td class="px-5 py-4">
                        <p class="font-medium">{{ position.name }}</p>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ position.code }}
                        </p>
                    </td>
                    <td class="px-5 py-4">
                        <p>{{ position.level.name }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ position.unit?.name || 'Lintas unit' }}
                        </p>
                    </td>
                    <td class="px-5 py-4">
                        <p
                            v-if="position.active_assignment"
                            class="font-medium"
                        >
                            {{ position.active_assignment.user.name }}
                        </p>
                        <p v-else class="text-muted-foreground">
                            Belum ditugaskan
                        </p>
                        <p
                            v-if="position.active_assignment"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Sejak
                            {{
                                new Date(
                                    position.active_assignment.started_at,
                                ).toLocaleDateString('id-ID')
                            }}
                        </p>
                    </td>
                    <td class="px-5 py-4">
                        <Badge
                            :variant="
                                position.active_assignment && position.is_active
                                    ? 'default'
                                    : 'secondary'
                            "
                            >{{ status(position) }}</Badge
                        >
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex justify-end gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="min-h-10"
                                @click="emit('history', position)"
                                ><History class="size-4" aria-hidden="true" />
                                Riwayat</Button
                            >
                            <Button
                                size="sm"
                                class="min-h-10 bg-violet-700 hover:bg-violet-800"
                                :disabled="!canMutate || !position.is_active"
                                @click="emit('manage', position)"
                                ><ArrowRightLeft
                                    v-if="position.active_assignment"
                                    class="size-4"
                                    aria-hidden="true"
                                /><UserPlus
                                    v-else
                                    class="size-4"
                                    aria-hidden="true"
                                />{{
                                    position.active_assignment
                                        ? 'Ganti'
                                        : 'Tugaskan'
                                }}</Button
                            >
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
