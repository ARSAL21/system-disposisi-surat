<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';
import OrganizationMutationSecurityBanner from '@/components/back-office/organization/OrganizationMutationSecurityBanner.vue';
import InstructionLabelDialog from '@/components/back-office/workflow/InstructionLabelDialog.vue';
import InstructionLabelList from '@/components/back-office/workflow/InstructionLabelList.vue';
import InstructionLabelStatusDialog from '@/components/back-office/workflow/InstructionLabelStatusDialog.vue';
import InstructionLabelWorkspaceHeader from '@/components/back-office/workflow/InstructionLabelWorkspaceHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { previewInstructionLabels } from '@/lib/dispositionPreview';
import type {
    DispositionInstructionLabel,
    InstructionLabelFilters,
    InstructionLabelPageProps,
    MutationSecurityState,
} from '@/types';

const props = defineProps<InstructionLabelPageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Instruksi Disposisi',
                href: '/back-office/workflow/instruction-labels',
            },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const previewLabels = ref<DispositionInstructionLabel[]>([
    ...previewInstructionLabels,
]);
const filters = reactive<InstructionLabelFilters>({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
});
const mutationSecurity = computed<MutationSecurityState>(() =>
    previewMode.value
        ? {
              can_manage: true,
              mfa_enabled: true,
              password_confirmed: true,
              password_confirmed_until: '2026-08-31T12:00:00+08:00',
              can_mutate: true,
              activation_url: '#preview-confirm',
              security_settings_url: '/settings/security',
          }
        : (props.mutationSecurity ?? {
              can_manage: false,
              mfa_enabled: false,
              password_confirmed: false,
              password_confirmed_until: null,
              can_mutate: false,
              activation_url: '',
              security_settings_url: '/settings/security',
          }),
);
const sourceLabels = computed(() =>
    previewMode.value ? previewLabels.value : (props.labels ?? []),
);
const visibleLabels = computed(() => {
    const search = filters.search.trim().toLocaleLowerCase('id-ID');

    return sourceLabels.value
        .filter((label) => {
            const matchesSearch =
                !search ||
                [label.name, label.code, label.description ?? ''].some(
                    (value) =>
                        value.toLocaleLowerCase('id-ID').includes(search),
                );
            const matchesStatus =
                filters.status === 'all' ||
                (filters.status === 'active' && label.is_active) ||
                (filters.status === 'inactive' && !label.is_active);

            return matchesSearch && matchesStatus;
        })
        .sort(
            (left, right) =>
                left.sort_order - right.sort_order || left.id - right.id,
        );
});
const activeLabelCount = computed(
    () => sourceLabels.value.filter((label) => label.is_active).length,
);

const selectedLabel = ref<DispositionInstructionLabel | null>(null);
const statusLabel = ref<DispositionInstructionLabel | null>(null);
const labelDialogOpen = ref(false);
const statusDialogOpen = ref(false);

const refreshList = useDebounceFn(() => {
    if (previewMode.value || !props.routes?.index) {
        return;
    }

    router.get(
        props.routes.index,
        { ...filters },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}, 300);

function updateFilters(patch: Partial<InstructionLabelFilters>): void {
    Object.assign(filters, patch);
    void refreshList();
}

function resetFilters(): void {
    Object.assign(filters, {
        search: '',
        status: 'all',
    } satisfies InstructionLabelFilters);
    void refreshList();
}

function openCreate(): void {
    selectedLabel.value = null;
    labelDialogOpen.value = true;
}

function openEdit(label: DispositionInstructionLabel): void {
    selectedLabel.value = label;
    labelDialogOpen.value = true;
}

function openStatus(label: DispositionInstructionLabel): void {
    statusLabel.value = label;
    statusDialogOpen.value = true;
}

function savePreview(payload: {
    label: DispositionInstructionLabel | null;
    code: string;
    name: string;
    description: string;
    sort_order: number;
}): void {
    if (payload.label) {
        previewLabels.value = previewLabels.value.map((label) =>
            label.id === payload.label?.id
                ? {
                      ...label,
                      code: payload.code,
                      name: payload.name,
                      description: payload.description || null,
                      sort_order: payload.sort_order,
                      updated_at: '2026-08-31T11:20:00+08:00',
                  }
                : label,
        );

        return;
    }

    const nextId =
        Math.max(...previewLabels.value.map((label) => label.id)) + 1;
    previewLabels.value = [
        ...previewLabels.value,
        {
            id: nextId,
            code: payload.code,
            name: payload.name,
            description: payload.description || null,
            sort_order: payload.sort_order,
            is_active: true,
            created_at: '2026-08-31T11:20:00+08:00',
            updated_at: '2026-08-31T11:20:00+08:00',
            links: {
                update: `#update-instruction-${nextId}`,
                status: `#status-instruction-${nextId}`,
            },
        },
    ];
}

function togglePreviewStatus(label: DispositionInstructionLabel): void {
    previewLabels.value = previewLabels.value.map((candidate) =>
        candidate.id === label.id
            ? {
                  ...candidate,
                  is_active: !candidate.is_active,
                  updated_at: '2026-08-31T11:22:00+08:00',
              }
            : candidate,
    );
}
</script>

<template>
    <Head title="Instruksi Disposisi" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <InstructionLabelWorkspaceHeader
            :can-mutate="mutationSecurity.can_mutate"
            :preview="previewMode"
            @create="openCreate"
        />
        <OrganizationMutationSecurityBanner
            :security="mutationSecurity"
            subject="instruksi disposisi"
        />

        <section class="rounded-2xl border bg-card p-4 shadow-xs">
            <div
                class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end"
            >
                <div class="space-y-2">
                    <Label for="instruction-label-search">Cari instruksi</Label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="instruction-label-search"
                            :model-value="filters.search"
                            class="min-h-11 pl-9"
                            placeholder="Nama, kode, atau deskripsi..."
                            @update:model-value="
                                updateFilters({ search: String($event) })
                            "
                        />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="instruction-label-status">Status</Label>
                    <Select
                        :model-value="filters.status"
                        @update:model-value="
                            updateFilters({
                                status: String(
                                    $event,
                                ) as InstructionLabelFilters['status'],
                            })
                        "
                    >
                        <SelectTrigger
                            id="instruction-label-status"
                            class="min-h-11 w-full"
                        >
                            <SelectValue placeholder="Semua status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            <SelectItem value="active">Aktif</SelectItem>
                            <SelectItem value="inactive">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    @click="resetFilters"
                >
                    <X class="size-4" aria-hidden="true" />
                    Atur ulang
                </Button>
            </div>
        </section>

        <InstructionLabelList
            :labels="visibleLabels"
            :can-mutate="mutationSecurity.can_mutate"
            @edit="openEdit"
            @status="openStatus"
            @reset="resetFilters"
        />

        <InstructionLabelDialog
            v-model:open="labelDialogOpen"
            :label="selectedLabel"
            :store-url="routes?.store"
            :preview="previewMode"
            @preview:save="savePreview"
        />
        <InstructionLabelStatusDialog
            v-if="statusLabel"
            v-model:open="statusDialogOpen"
            :label="statusLabel"
            :can-change-status="!statusLabel.is_active || activeLabelCount > 1"
            :preview="previewMode"
            @preview:confirm="togglePreviewStatus"
        />
    </main>
</template>
