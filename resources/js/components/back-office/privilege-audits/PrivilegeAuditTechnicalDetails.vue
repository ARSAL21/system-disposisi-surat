<script setup lang="ts">
import {
    Fingerprint,
    GlobeLock,
    LaptopMinimal,
    TerminalSquare,
} from '@lucide/vue';
import type { PrivilegeAuditRecord } from '@/types';

defineProps<{ audit: PrivilegeAuditRecord }>();
</script>

<template>
    <section class="rounded-2xl border bg-slate-50/60 p-4 dark:bg-slate-900/35">
        <h3 class="text-sm font-semibold">Konteks teknis</h3>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="min-w-0">
                <dt
                    class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                >
                    <Fingerprint class="size-3.5" aria-hidden="true" />
                    Request ID
                </dt>
                <dd class="mt-1 font-mono text-xs break-all">
                    {{ audit.request_id ?? 'Tidak tersedia' }}
                </dd>
            </div>
            <div class="min-w-0">
                <dt
                    class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                >
                    <GlobeLock class="size-3.5" aria-hidden="true" />
                    IP address
                </dt>
                <dd class="mt-1 text-sm">
                    {{ audit.ip_address ?? 'Console / tidak tersedia' }}
                </dd>
            </div>
            <div v-if="audit.command" class="min-w-0 sm:col-span-2">
                <dt
                    class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                >
                    <TerminalSquare class="size-3.5" aria-hidden="true" />
                    Command
                </dt>
                <dd class="mt-1 font-mono text-xs break-all">
                    {{ audit.command }}
                </dd>
            </div>
            <div class="min-w-0 sm:col-span-2">
                <dt
                    class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                >
                    <LaptopMinimal class="size-3.5" aria-hidden="true" />
                    User agent
                </dt>
                <dd class="mt-1 text-xs leading-5 break-words">
                    {{ audit.user_agent ?? 'Console / tidak tersedia' }}
                </dd>
            </div>
        </dl>
    </section>
</template>
