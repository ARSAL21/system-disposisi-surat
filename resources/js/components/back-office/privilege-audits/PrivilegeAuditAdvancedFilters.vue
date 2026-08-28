<script setup lang="ts">
import { CalendarDays, Search, UserRoundSearch } from '@lucide/vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { PrivilegeAuditFilters } from '@/types';

defineProps<{ filters: PrivilegeAuditFilters }>();

defineEmits<{
    'update:actor': [value: string];
    'update:target': [value: string];
    'update:date-from': [value: string];
    'update:date-to': [value: string];
}>();
</script>

<template>
    <div class="grid gap-4 border-t pt-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="space-y-2">
            <Label for="audit-actor">Actor</Label>
            <div class="relative">
                <UserRoundSearch
                    class="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="audit-actor"
                    :model-value="filters.actor"
                    class="h-11 pl-9"
                    placeholder="Nama atau email actor"
                    autocomplete="off"
                    @update:model-value="$emit('update:actor', String($event))"
                />
            </div>
        </div>
        <div class="space-y-2">
            <Label for="audit-target">Target</Label>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="audit-target"
                    :model-value="filters.target"
                    class="h-11 pl-9"
                    placeholder="ID, nama, atau email target"
                    autocomplete="off"
                    @update:model-value="$emit('update:target', String($event))"
                />
            </div>
        </div>
        <div class="space-y-2">
            <Label for="audit-date-from">Mulai tanggal</Label>
            <div class="relative">
                <CalendarDays
                    class="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="audit-date-from"
                    type="date"
                    :model-value="filters.date_from"
                    class="h-11 pl-9"
                    @update:model-value="
                        $emit('update:date-from', String($event))
                    "
                />
            </div>
        </div>
        <div class="space-y-2">
            <Label for="audit-date-to">Sampai tanggal</Label>
            <div class="relative">
                <CalendarDays
                    class="pointer-events-none absolute top-3 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="audit-date-to"
                    type="date"
                    :min="filters.date_from || undefined"
                    :model-value="filters.date_to"
                    class="h-11 pl-9"
                    @update:model-value="
                        $emit('update:date-to', String($event))
                    "
                />
            </div>
        </div>
    </div>
</template>
