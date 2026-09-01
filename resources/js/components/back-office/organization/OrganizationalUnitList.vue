<script setup lang="ts">
import {
    Building2,
    MoreHorizontal,
    Network,
    Pencil,
    Plus,
    Power,
    TableProperties,
} from '@lucide/vue';
import { ref } from 'vue';
import OrganizationChartViewer from '@/components/back-office/organization/OrganizationChartViewer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    OrganizationalUnit,
    OrganizationTreeData,
    Paginated,
} from '@/types';

defineProps<{
    units: Paginated<OrganizationalUnit>;
    tree: OrganizationTreeData;
    assignmentsRoute: string;
    canMutate: boolean;
}>();

const emit = defineEmits<{
    create: [];
    edit: [unit: OrganizationalUnit];
    status: [unit: OrganizationalUnit];
    createPosition: [];
}>();

const displayMode = ref<'table' | 'chart'>('table');
</script>

<template>
    <div class="space-y-6">
        <!-- View Switcher & Action Header Card -->
        <div
            class="flex flex-col gap-4 rounded-2xl border bg-card p-4 sm:flex-row sm:items-center sm:justify-between shadow-sm"
        >
            <div>
                <h2 class="text-base font-bold text-foreground sm:text-lg">
                    {{
                        displayMode === 'chart'
                            ? 'Bagan Struktur & Unit Organisasi'
                            : 'Direktori Unit Organisasi'
                    }}
                </h2>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{
                        displayMode === 'chart'
                            ? 'Visualisasi hubungan hierarki induk-anak dan posisi pejabat di setiap unit/bagian.'
                            : 'Daftar tabel relasi induk, jumlah jabatan, dan status operasional setiap unit kerja.'
                    }}
                </p>
            </div>

            <!-- Buttons: Switch View & Add Unit -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Toggle View Mode Button -->
                <div
                    class="flex rounded-xl border border-border/80 bg-muted/50 p-1 text-xs"
                >
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition-colors"
                        :class="[
                            displayMode === 'table'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="displayMode = 'table'"
                    >
                        <TableProperties class="size-3.5" />
                        <span>Tabel Unit</span>
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition-colors"
                        :class="[
                            displayMode === 'chart'
                                ? 'bg-indigo-600 text-white shadow-sm dark:bg-indigo-500'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="displayMode = 'chart'"
                    >
                        <Network class="size-3.5" />
                        <span>Bagan Interaktif</span>
                    </button>
                </div>

                <Button
                    class="min-h-10 gap-1.5 rounded-xl bg-blue-700 hover:bg-blue-800 text-xs font-semibold text-white shadow-sm"
                    :disabled="!canMutate"
                    @click="emit('create')"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    <span>Tambah Unit</span>
                </Button>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- VIEW 1: FULL INTERACTIVE ORGANOGRAM CHART                -->
        <!-- ======================================================== -->
        <div v-if="displayMode === 'chart'">
            <OrganizationChartViewer
                :tree="tree"
                :assignments-route="assignmentsRoute"
                :can-mutate="canMutate"
                @create-unit="emit('create')"
                @create-position="emit('createPosition')"
            />
        </div>

        <!-- ======================================================== -->
        <!-- VIEW 2: STANDARD DIRECTORY TABLE                         -->
        <!-- ======================================================== -->
        <section
            v-else
            class="overflow-hidden rounded-2xl border bg-card shadow-sm"
        >
            <div v-if="units.data.length" class="divide-y">
                <article
                    v-for="unit in units.data"
                    :key="unit.id"
                    class="grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                >
                    <div class="flex min-w-0 gap-3">
                        <span
                            class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            <Building2 class="size-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate font-medium text-foreground">
                                    {{ unit.name }}
                                </h3>
                                <Badge
                                    :variant="
                                        unit.is_active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ unit.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>
                            <p class="mt-1 truncate text-sm text-muted-foreground">
                                {{ unit.code || 'Tanpa kode' }} · Induk:
                                {{ unit.parent?.name || 'Root (Unit Utama)' }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ unit.children_count }} unit turunan ·
                                {{ unit.positions_count }} jabatan
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            class="min-h-10 rounded-xl"
                            :disabled="!canMutate || !unit.capabilities.update"
                            @click="emit('edit', unit)"
                        >
                            <Pencil class="size-4" aria-hidden="true" />
                            <span>Ubah</span>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-10 rounded-xl"
                            :disabled="
                                !canMutate || !unit.capabilities.change_status
                            "
                            :aria-label="`${unit.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${unit.name}`"
                            @click="emit('status', unit)"
                        >
                            <Power class="size-4" aria-hidden="true" />
                            <MoreHorizontal class="sr-only" />
                        </Button>
                    </div>
                </article>
            </div>
            <div v-else class="grid min-h-56 place-items-center p-8 text-center">
                <div>
                    <Building2
                        class="mx-auto size-8 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p class="mt-3 font-medium">Belum ada unit yang cocok</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Sesuaikan filter atau buat unit baru.
                    </p>
                </div>
            </div>
            <slot name="pagination" />
        </section>
    </div>
</template>
