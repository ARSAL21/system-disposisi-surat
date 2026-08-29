<script setup lang="ts">
import {
    BriefcaseBusiness,
    CircleUserRound,
    Pencil,
    Plus,
    Power,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationPosition, Paginated } from '@/types';

defineProps<{
    positions: Paginated<OrganizationPosition>;
    canMutate: boolean;
}>();
const emit = defineEmits<{
    create: [];
    edit: [position: OrganizationPosition];
    status: [position: OrganizationPosition];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <header
            class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="font-semibold">Direktori jabatan</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Jabatan konkret yang akan menjadi tujuan routing berbasis
                    posisi.
                </p>
            </div>
            <Button
                class="min-h-11 bg-blue-700 hover:bg-blue-800"
                :disabled="!canMutate"
                @click="emit('create')"
                ><Plus class="size-4" aria-hidden="true" /> Tambah
                jabatan</Button
            >
        </header>
        <div v-if="positions.data.length" class="grid gap-3 p-3 lg:grid-cols-2">
            <article
                v-for="position in positions.data"
                :key="position.id"
                class="rounded-2xl border p-4 transition-colors hover:border-blue-200 dark:hover:border-blue-900"
            >
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="grid size-10 shrink-0 place-items-center rounded-xl bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300"
                        ><BriefcaseBusiness class="size-5" aria-hidden="true"
                    /></span>
                    <Badge
                        :variant="position.is_active ? 'default' : 'secondary'"
                        >{{ position.is_active ? 'Aktif' : 'Nonaktif' }}</Badge
                    >
                </div>
                <p
                    class="mt-4 font-mono text-xs text-violet-700 dark:text-violet-300"
                >
                    {{ position.code }}
                </p>
                <h3 class="mt-1 font-semibold">{{ position.name }}</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ position.level.name }} ·
                    {{ position.unit?.name || 'Lintas unit' }}
                </p>
                <div
                    class="mt-4 flex items-center gap-2 rounded-xl bg-muted/50 p-3 text-sm"
                >
                    <CircleUserRound
                        class="size-4 text-blue-600"
                        aria-hidden="true"
                    />
                    <span v-if="position.active_assignment" class="truncate"
                        ><strong class="font-medium">{{
                            position.active_assignment.user.name
                        }}</strong>
                        · sejak
                        {{
                            new Date(
                                position.active_assignment.started_at,
                            ).toLocaleDateString('id-ID')
                        }}</span
                    >
                    <span v-else class="text-muted-foreground"
                        >Jabatan sedang lowong</span
                    >
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="min-h-10"
                        :disabled="!canMutate || !position.capabilities.update"
                        @click="emit('edit', position)"
                        ><Pencil class="size-4" aria-hidden="true" />
                        Ubah</Button
                    >
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-10"
                        :disabled="
                            !canMutate || !position.capabilities.change_status
                        "
                        :aria-label="`${position.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${position.name}`"
                        @click="emit('status', position)"
                        ><Power class="size-4" aria-hidden="true"
                    /></Button>
                </div>
            </article>
        </div>
        <div v-else class="grid min-h-56 place-items-center p-8 text-center">
            <div>
                <BriefcaseBusiness
                    class="mx-auto size-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mt-3 font-medium">Belum ada jabatan yang cocok</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Sesuaikan filter atau buat jabatan baru.
                </p>
            </div>
        </div>
        <slot name="pagination" />
    </section>
</template>
