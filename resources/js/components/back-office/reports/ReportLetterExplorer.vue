<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, ChevronRight, FileSearch, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import RoutingPagination from '@/components/back-office/routing/RoutingPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { formatRoutingDateTime } from '@/lib/letterRoutingPresentation';
import type { PaginatedPeriodicReportLetters } from '@/types';

const props = defineProps<{
    letters: PaginatedPeriodicReportLetters;
    selectedReference?: string | null;
}>();

const search = ref('');

const filteredLetters = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('id-ID');

    if (!query) {
        return props.letters.data;
    }

    return props.letters.data.filter((letter) =>
        [
            letter.agenda_number,
            letter.subject,
            letter.sender_organization_name,
        ].some((value) => value.toLocaleLowerCase('id-ID').includes(query)),
    );
});
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="border-b px-4 pb-4">
            <label class="sr-only" for="report-letter-search">Cari surat</label>
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    id="report-letter-search"
                    v-model="search"
                    class="pl-9"
                    placeholder="Cari agenda, perihal, atau instansi"
                />
            </div>
        </div>

        <div
            v-if="filteredLetters.length"
            class="min-h-0 flex-1 overflow-y-auto p-3"
        >
            <ul class="space-y-2">
                <li v-for="letter in filteredLetters" :key="letter.reference">
                    <Link
                        :href="letter.links.detail"
                        class="group block rounded-xl border p-3 transition-colors hover:border-indigo-300 hover:bg-indigo-50/40 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none dark:hover:border-indigo-800 dark:hover:bg-indigo-950/20"
                        :class="
                            selectedReference === letter.reference
                                ? 'border-indigo-500 bg-indigo-50/70 dark:bg-indigo-950/30'
                                : 'bg-background'
                        "
                        :aria-current="
                            selectedReference === letter.reference
                                ? 'page'
                                : undefined
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-300"
                                >
                                    {{ letter.agenda_number }}
                                </p>
                                <h3
                                    class="mt-1 line-clamp-2 text-sm leading-5 font-semibold"
                                >
                                    {{ letter.subject }}
                                </h3>
                            </div>
                            <ChevronRight
                                class="mt-1 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                            />
                        </div>
                        <p class="mt-2 truncate text-xs text-muted-foreground">
                            {{ letter.sender_organization_name }}
                        </p>
                        <div
                            class="mt-3 flex items-center justify-between gap-2"
                        >
                            <Badge
                                :variant="
                                    letter.status === 'COMPLETED'
                                        ? 'default'
                                        : 'secondary'
                                "
                                class="text-[10px]"
                            >
                                <CheckCircle2
                                    v-if="letter.status === 'COMPLETED'"
                                    class="size-3"
                                />
                                {{
                                    letter.status === 'COMPLETED'
                                        ? 'Selesai'
                                        : `${letter.branch_progress.completed}/${letter.branch_progress.total} cabang`
                                }}
                            </Badge>
                            <span
                                class="text-[10px] text-muted-foreground tabular-nums"
                            >
                                {{ formatRoutingDateTime(letter.received_at) }}
                            </span>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>

        <div
            v-else
            class="grid min-h-64 flex-1 place-items-center p-8 text-center"
        >
            <div>
                <FileSearch class="mx-auto size-8 text-muted-foreground" />
                <p class="mt-3 text-sm font-semibold">Surat tidak ditemukan</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Ubah kata pencarian atau filter periode.
                </p>
            </div>
        </div>

        <RoutingPagination :pagination="letters.pagination" />
    </div>
</template>
