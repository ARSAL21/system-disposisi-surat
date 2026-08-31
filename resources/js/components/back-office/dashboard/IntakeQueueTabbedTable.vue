<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    Eye,
    FileText,
    Inbox,
    Search,
    User,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatBytes } from '@/lib/documentVersionPreview';
import {
    formatSubmissionDate,
    getSubmissionStatusMeta,
} from '@/lib/intakeDashboardPreview';
import type { IntakeQueueItem } from '@/types';

const props = defineProps<{
    submissions: IntakeQueueItem[];
    activeTab: string;
}>();

const emit = defineEmits<{
    'update:activeTab': [tab: string];
    openReview: [submission: IntakeQueueItem];
}>();

const searchQuery = ref('');

const tabs = [
    { key: 'ALL', label: 'Semua Tugas' },
    { key: 'SUBMITTED', label: 'Perlu Screening' },
    { key: 'INTERNAL_REVISION_REQUIRED', label: 'Perbaikan dari Kabag' },
];

const filteredSubmissions = computed(() => {
    return props.submissions.filter((item) => {
        // Tab filter
        const matchTab =
            props.activeTab === 'ALL' || item.status === props.activeTab;

        if (!matchTab) {
            return false;
        }

        // Search query filter
        if (!searchQuery.value.trim()) {
            return true;
        }

        const query = searchQuery.value.toLowerCase();
        const subjectMatch =
            item.subject?.toLowerCase().includes(query) ?? false;
        const senderMatch =
            item.sender_organization_name?.toLowerCase().includes(query) ??
            false;
        const contactMatch =
            item.contact_name?.toLowerCase().includes(query) ?? false;
        const letterNumberMatch =
            item.external_letter_number?.toLowerCase().includes(query) ?? false;

        return subjectMatch || senderMatch || contactMatch || letterNumberMatch;
    });
});

function getTabCount(tabKey: string): number {
    if (tabKey === 'ALL') {
        return props.submissions.length;
    }

    return props.submissions.filter((item) => item.status === tabKey).length;
}
</script>

<template>
    <Card class="border-border/80 shadow-xs">
        <CardHeader class="border-b border-border/60 p-4 sm:p-6">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <Inbox class="size-5 text-primary" aria-hidden="true" />
                        <CardTitle
                            class="text-lg font-bold tracking-tight text-foreground sm:text-xl"
                        >
                            Antrean Kerja Harian Petugas
                        </CardTitle>
                    </div>
                    <CardDescription class="mt-1 text-xs text-muted-foreground">
                        Maksimal 10 tugas screening dan perbaikan internal
                        terbaru yang memerlukan respon aktif.
                    </CardDescription>
                </div>

                <!-- Search Input Bar -->
                <div class="relative w-full sm:w-72 lg:w-80">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari pengirim, nomor, perihal..."
                        class="w-full rounded-xl border border-border bg-background py-2 pr-8 pl-9 text-xs transition-colors outline-none placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/30"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        class="absolute top-1/2 right-2.5 -translate-y-1/2 cursor-pointer text-muted-foreground hover:text-foreground"
                        @click="searchQuery = ''"
                    >
                        <X class="size-3.5" aria-hidden="true" />
                        <span class="sr-only">Hapus pencarian</span>
                    </button>
                </div>
            </div>

            <!-- Tab Filter Buttons Navigation -->
            <div
                class="flex flex-wrap items-center gap-1.5 overflow-x-auto pt-3"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                    :class="[
                        activeTab === tab.key
                            ? 'bg-primary text-primary-foreground shadow-xs'
                            : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                    @click="emit('update:activeTab', tab.key)"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        class="py-0.2 rounded-full px-1.5 text-[10px] font-bold"
                        :class="[
                            activeTab === tab.key
                                ? 'bg-primary-foreground/20 text-primary-foreground'
                                : 'bg-background text-muted-foreground',
                        ]"
                    >
                        {{ getTabCount(tab.key) }}
                    </span>
                </button>
            </div>
        </CardHeader>

        <CardContent class="p-0">
            <!-- Table View when items exist -->
            <div v-if="filteredSubmissions.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead
                        class="border-b border-border bg-muted/40 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th scope="col" class="py-3 pr-4 pl-4 sm:pl-6">
                                Pengirim & Kontak
                            </th>
                            <th scope="col" class="px-4 py-3">
                                Perihal & Dokumen
                            </th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">Waktu Masuk</th>
                            <th
                                scope="col"
                                class="py-3 pr-4 pl-4 text-right sm:pr-6"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr
                            v-for="item in filteredSubmissions"
                            :key="item.public_id"
                            class="group transition-colors hover:bg-muted/30"
                        >
                            <!-- Sender Column -->
                            <td
                                class="max-w-[14rem] py-3.5 pr-4 pl-4 align-top sm:pl-6"
                            >
                                <div
                                    class="flex items-center gap-1.5 truncate font-bold text-foreground"
                                >
                                    <Building2
                                        class="size-3.5 shrink-0 text-primary"
                                        aria-hidden="true"
                                    />
                                    <span class="truncate">{{
                                        item.sender_organization_name
                                    }}</span>
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-1 truncate text-[11px] text-muted-foreground"
                                >
                                    <User
                                        class="size-3 shrink-0 text-muted-foreground/70"
                                        aria-hidden="true"
                                    />
                                    <span class="truncate">{{
                                        item.contact_name
                                    }}</span>
                                </div>
                                <div class="mt-1">
                                    <Badge
                                        variant="outline"
                                        class="px-1.5 py-0 text-[10px]"
                                    >
                                        {{
                                            item.source === 'ONLINE'
                                                ? 'Online'
                                                : 'Loket'
                                        }}
                                    </Badge>
                                </div>
                            </td>

                            <!-- Subject & Document Column -->
                            <td class="max-w-[20rem] px-4 py-3.5 align-top">
                                <div
                                    class="line-clamp-2 cursor-pointer leading-snug font-semibold text-foreground hover:text-primary"
                                    @click="emit('openReview', item)"
                                >
                                    {{ item.subject }}
                                </div>
                                <div
                                    v-if="item.external_letter_number"
                                    class="mt-1 font-mono text-[11px] text-muted-foreground"
                                >
                                    No: {{ item.external_letter_number }}
                                </div>
                                <div
                                    v-if="item.document"
                                    class="mt-1.5 flex items-center gap-1 text-[11px] text-muted-foreground"
                                >
                                    <FileText
                                        class="size-3 shrink-0 text-primary"
                                        aria-hidden="true"
                                    />
                                    <span class="max-w-[12rem] truncate">{{
                                        item.document.original_filename
                                    }}</span>
                                    <span
                                        >({{
                                            formatBytes(
                                                item.document.size_bytes,
                                            )
                                        }})</span
                                    >
                                </div>
                            </td>

                            <!-- Status Column -->
                            <td class="px-4 py-3.5 align-top">
                                <Badge
                                    variant="outline"
                                    class="gap-1 px-2 py-0.5 text-[11px] font-semibold"
                                    :class="
                                        getSubmissionStatusMeta(item.status)
                                            .badgeClass
                                    "
                                >
                                    <span
                                        class="size-1.5 rounded-full"
                                        :class="
                                            getSubmissionStatusMeta(item.status)
                                                .dotClass
                                        "
                                        aria-hidden="true"
                                    />
                                    <span>{{
                                        getSubmissionStatusMeta(item.status)
                                            .label
                                    }}</span>
                                </Badge>
                                <div
                                    v-if="
                                        item.status ===
                                        'INTERNAL_REVISION_REQUIRED'
                                    "
                                    class="mt-1 text-[10px] font-medium text-rose-600 dark:text-rose-400"
                                >
                                    Perlu tindak lanjut staf
                                </div>
                            </td>

                            <!-- Time Column -->
                            <td
                                class="px-4 py-3.5 align-top text-[11px] whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatSubmissionDate(item.submitted_at) }}
                            </td>

                            <!-- Actions Column -->
                            <td
                                class="py-3.5 pr-4 pl-4 text-right align-top sm:pr-6"
                            >
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 cursor-pointer gap-1 text-xs"
                                        @click="emit('openReview', item)"
                                    >
                                        <Eye
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        <span>Pratinjau</span>
                                    </Button>

                                    <Button
                                        as-child
                                        size="sm"
                                        class="h-8 gap-1 bg-primary text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                                    >
                                        <Link :href="item.links.show">
                                            <span>Periksa</span>
                                            <ArrowRight
                                                class="size-3"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State when no items match -->
            <div
                v-else
                class="flex flex-col items-center justify-center space-y-3 p-10 text-center"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
                >
                    <CheckCircle2 class="size-6" aria-hidden="true" />
                </div>
                <div class="space-y-1">
                    <h4 class="text-sm font-semibold text-foreground">
                        Tidak Ada Pengajuan dalam Antrean
                    </h4>
                    <p class="max-w-sm text-xs text-muted-foreground">
                        {{
                            searchQuery
                                ? `Tidak ditemukan pengajuan dengan kata kunci "${searchQuery}".`
                                : 'Seluruh pengajuan pada kategori ini telah diproses dengan lengkap.'
                        }}
                    </p>
                </div>
                <Button
                    v-if="searchQuery || activeTab !== 'ALL'"
                    type="button"
                    variant="outline"
                    size="sm"
                    class="mt-1 cursor-pointer text-xs"
                    @click="
                        searchQuery = '';
                        emit('update:activeTab', 'ALL');
                    "
                >
                    Tampilkan Semua Tugas
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
