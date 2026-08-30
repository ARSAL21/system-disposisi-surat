<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    LetterActivityAction,
    LetterActivityFilterOptions,
    LetterActivityVisibility,
} from '@/types';

const props = defineProps<{
    action: LetterActivityAction | '';
    source: 'public' | 'internal' | '';
    actor: string;
    options: LetterActivityFilterOptions;
    visibility: LetterActivityVisibility;
}>();
const emit = defineEmits<{
    'update:action': [value: LetterActivityAction | ''];
    'update:source': [value: 'public' | 'internal' | ''];
    'update:actor': [value: string];
}>();

const actionValue = computed({
    get: () => props.action || 'all',
    set: (value: string) =>
        emit(
            'update:action',
            value === 'all' ? '' : (value as LetterActivityAction),
        ),
});
const sourceValue = computed({
    get: () => props.source || 'all',
    set: (value: string) =>
        emit(
            'update:source',
            value === 'all' ? '' : (value as 'public' | 'internal'),
        ),
});
const actorValue = computed({
    get: () => props.actor || 'all',
    set: (value: string) => emit('update:actor', value === 'all' ? '' : value),
});
</script>

<template>
    <div class="space-y-2">
        <Label for="activity-action">Jenis aktivitas</Label>
        <Select v-model="actionValue">
            <SelectTrigger id="activity-action" class="h-11 w-full">
                <SelectValue placeholder="Semua aktivitas" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Semua aktivitas</SelectItem>
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
        <Label for="activity-source">Sumber aktivitas</Label>
        <Select v-model="sourceValue">
            <SelectTrigger id="activity-source" class="h-11 w-full">
                <SelectValue placeholder="Semua sumber" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Semua sumber</SelectItem>
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

    <div v-if="visibility === 'details'" class="space-y-2">
        <Label for="activity-actor">Ditangani oleh</Label>
        <Select v-model="actorValue">
            <SelectTrigger id="activity-actor" class="h-11 w-full">
                <SelectValue placeholder="Semua pelaksana" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">Semua pelaksana</SelectItem>
                <SelectItem
                    v-for="option in options.actors"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
