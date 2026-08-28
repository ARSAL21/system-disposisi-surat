<script setup lang="ts">
import { Filter, RotateCcw, Search } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import PrivilegeAuditAdvancedFilters from '@/components/back-office/privilege-audits/PrivilegeAuditAdvancedFilters.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type {
    PrivilegeAuditAction,
    PrivilegeAuditFilterOptions,
    PrivilegeAuditFilters,
    PrivilegeAuditSource,
    PrivilegeAuditTargetType,
} from '@/types';

const props = defineProps<{
    filters: PrivilegeAuditFilters;
    options: PrivilegeAuditFilterOptions;
    processing: boolean;
}>();
const emit = defineEmits<{
    apply: [filters: PrivilegeAuditFilters];
    reset: [];
}>();

const form = reactive<PrivilegeAuditFilters>({ ...props.filters });
const action = computed({
    get: () => form.action || 'all',
    set: (value: string) =>
        (form.action = value === 'all' ? '' : (value as PrivilegeAuditAction)),
});
const source = computed({
    get: () => form.source || 'all',
    set: (value: string) =>
        (form.source = value === 'all' ? '' : (value as PrivilegeAuditSource)),
});
const targetType = computed({
    get: () => form.target_type || 'all',
    set: (value: string) =>
        (form.target_type =
            value === 'all' ? '' : (value as PrivilegeAuditTargetType)),
});
const hasFilters = computed(() => Object.values(form).some(Boolean));

watch(
    () => props.filters,
    (filters) => Object.assign(form, filters),
    { deep: true },
);

function submit(): void {
    emit('apply', { ...form });
}
</script>

<template>
    <Card class="gap-0 overflow-hidden py-0 shadow-sm">
        <CardHeader
            class="border-b bg-slate-50/60 px-5 py-4 dark:bg-slate-900/40"
        >
            <CardTitle class="flex items-center gap-2 text-base">
                <Filter class="size-4 text-indigo-600" aria-hidden="true" />
                Filter audit
            </CardTitle>
        </CardHeader>
        <CardContent class="p-5">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="audit-action">Jenis perubahan</Label>
                        <Select v-model="action">
                            <SelectTrigger
                                id="audit-action"
                                class="h-11 w-full"
                            >
                                <SelectValue placeholder="Semua aksi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua aksi</SelectItem>
                                <SelectItem
                                    v-for="option in options.actions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label for="audit-source">Sumber</Label>
                        <Select v-model="source">
                            <SelectTrigger
                                id="audit-source"
                                class="h-11 w-full"
                            >
                                <SelectValue placeholder="Semua sumber" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >Semua sumber</SelectItem
                                >
                                <SelectItem
                                    v-for="option in options.sources"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label for="audit-target-type">Tipe target</Label>
                        <Select v-model="targetType">
                            <SelectTrigger
                                id="audit-target-type"
                                class="h-11 w-full"
                            >
                                <SelectValue placeholder="Semua target" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >Semua target</SelectItem
                                >
                                <SelectItem
                                    v-for="option in options.target_types"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <PrivilegeAuditAdvancedFilters
                    :filters="form"
                    @update:actor="form.actor = $event"
                    @update:target="form.target = $event"
                    @update:date-from="form.date_from = $event"
                    @update:date-to="form.date_to = $event"
                />

                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="min-h-11"
                        :disabled="!hasFilters || processing"
                        @click="emit('reset')"
                    >
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Reset filter
                    </Button>
                    <Button
                        type="submit"
                        class="min-h-11"
                        :disabled="processing"
                    >
                        <Spinner
                            v-if="processing"
                            class="size-4 motion-reduce:animate-none"
                            aria-hidden="true"
                        />
                        <Search v-else class="size-4" aria-hidden="true" />
                        Terapkan filter
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
