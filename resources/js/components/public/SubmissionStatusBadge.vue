<script setup lang="ts">
import { computed } from 'vue';
import { getSubmissionStatusPresentation } from '@/lib/submissionPresentation';
import { cn } from '@/lib/utils';
import type { SubmissionStatus } from '@/types';

const props = defineProps<{
    status: SubmissionStatus;
    showDescription?: boolean;
}>();

const presentation = computed(() =>
    getSubmissionStatusPresentation(props.status),
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold',
                presentation.badgeClass,
            )
        "
    >
        <span
            :class="cn('size-1.5 rounded-full', presentation.dotClass)"
            aria-hidden="true"
        />
        {{ presentation.label }}
        <span v-if="showDescription" class="sr-only">
            — {{ presentation.description }}
        </span>
    </span>
</template>
