<script setup lang="ts">
import { Eye } from '@lucide/vue';
import { computed } from 'vue';
import PrivilegeAuditChangePanel from '@/components/back-office/privilege-audits/PrivilegeAuditChangePanel.vue';
import PrivilegeAuditContext from '@/components/back-office/privilege-audits/PrivilegeAuditContext.vue';
import PrivilegeAuditTechnicalDetails from '@/components/back-office/privilege-audits/PrivilegeAuditTechnicalDetails.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import {
    privilegeAuditActionClass,
    privilegeAuditActionLabels,
} from '@/lib/privilegeAuditPresentation';
import type { PrivilegeAuditRecord } from '@/types';

const props = defineProps<{
    open: boolean;
    audit: PrivilegeAuditRecord | null;
}>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const dialogOpen = computed({
    get: () => props.open,
    set: (value: boolean) => emit('update:open', value),
});
</script>

<template>
    <Dialog v-model:open="dialogOpen">
        <DialogScrollContent class="sm:max-w-3xl">
            <template v-if="audit">
                <DialogHeader>
                    <div class="mb-2 flex flex-wrap items-center gap-2 pr-8">
                        <span
                            class="flex size-11 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-700 dark:text-indigo-300"
                        >
                            <Eye class="size-5" aria-hidden="true" />
                        </span>
                        <Badge
                            variant="outline"
                            :class="privilegeAuditActionClass(audit.action)"
                        >
                            {{ privilegeAuditActionLabels[audit.action] }}
                        </Badge>
                    </div>
                    <DialogTitle>Detail audit #{{ audit.id }}</DialogTitle>
                    <DialogDescription class="leading-6">
                        Catatan ini bersifat read-only. Nilai ditampilkan dari
                        Payload audit dibatasi dan disanitasi oleh server
                        sebelum dikirim ke halaman.
                    </DialogDescription>
                </DialogHeader>

                <PrivilegeAuditContext :audit="audit" />

                <div class="grid gap-3 lg:grid-cols-2">
                    <PrivilegeAuditChangePanel
                        title="Sebelum"
                        :changes="audit.before"
                        tone="before"
                    />
                    <PrivilegeAuditChangePanel
                        title="Sesudah"
                        :changes="audit.after"
                        tone="after"
                    />
                </div>

                <PrivilegeAuditTechnicalDetails :audit="audit" />

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline" class="min-h-11"
                            >Tutup</Button
                        >
                    </DialogClose>
                </DialogFooter>
            </template>
            <div v-else class="space-y-4" aria-label="Memuat detail audit">
                <Skeleton class="h-7 w-48 motion-reduce:animate-none" />
                <Skeleton class="h-24 w-full motion-reduce:animate-none" />
                <Skeleton class="h-40 w-full motion-reduce:animate-none" />
            </div>
        </DialogScrollContent>
    </Dialog>
</template>
