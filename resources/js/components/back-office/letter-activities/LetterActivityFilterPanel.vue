<script setup lang="ts">
import { Filter, RotateCcw, Search } from '@lucide/vue';
import { reactive, watch } from 'vue';
import LetterActivitySelectFilters from '@/components/back-office/letter-activities/LetterActivitySelectFilters.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type {
    LetterActivityFilterOptions,
    LetterActivityFilters,
    LetterActivityVisibility,
} from '@/types';

const props = defineProps<{
    filters: LetterActivityFilters;
    options: LetterActivityFilterOptions;
    processing: boolean;
    visibility: LetterActivityVisibility;
}>();
const emit = defineEmits<{
    apply: [filters: LetterActivityFilters];
    reset: [];
}>();

const form = reactive<LetterActivityFilters>({ ...props.filters });
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
                Filter aktivitas
            </CardTitle>
        </CardHeader>
        <CardContent class="p-5">
            <form class="space-y-4" @submit.prevent="submit">
                <div
                    :class="[
                        'grid gap-4 md:grid-cols-2',
                        visibility === 'details'
                            ? 'xl:grid-cols-5'
                            : 'xl:grid-cols-4',
                    ]"
                >
                    <LetterActivitySelectFilters
                        :action="form.action"
                        :source="form.source"
                        :actor="form.actor"
                        :options="options"
                        :visibility="visibility"
                        @update:action="form.action = $event"
                        @update:source="form.source = $event"
                        @update:actor="form.actor = $event"
                    />

                    <div class="space-y-2">
                        <Label for="activity-date-from">Dari tanggal</Label>
                        <Input
                            id="activity-date-from"
                            v-model="form.date_from"
                            type="date"
                            class="h-11"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="activity-date-to">Sampai tanggal</Label>
                        <Input
                            id="activity-date-to"
                            v-model="form.date_to"
                            type="date"
                            class="h-11"
                        />
                    </div>
                </div>

                <div
                    v-if="visibility === 'details'"
                    class="space-y-2"
                >
                    <Label for="activity-letter">Cari surat</Label>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="activity-letter"
                            v-model="form.letter"
                            class="h-11 pl-9"
                            placeholder="Nomor agenda, ID pengajuan, perihal, atau pengirim"
                            autocomplete="off"
                        />
                    </div>
                </div>

                <div
                    class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        class="min-h-11"
                        :disabled="processing"
                        @click="emit('reset')"
                    >
                        <RotateCcw class="size-4" aria-hidden="true" />
                        Kembali ke hari ini
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
