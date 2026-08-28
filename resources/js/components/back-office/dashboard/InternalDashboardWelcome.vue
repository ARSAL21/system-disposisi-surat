<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

defineProps<{
    userName: string;
}>();
</script>

<template>
    <section
        class="relative overflow-hidden rounded-2xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/80 to-violet-50 p-6 shadow-sm sm:p-8 dark:border-blue-900/40 dark:from-slate-950 dark:via-blue-950/50 dark:to-violet-950/40"
    >
        <div
            aria-hidden="true"
            class="absolute -top-20 -right-20 size-56 rounded-full bg-violet-400/15 blur-3xl"
        />
        <div class="relative max-w-2xl">
            <Badge
                variant="outline"
                class="mb-5 border-blue-300/60 bg-blue-50/80 text-blue-700 dark:border-blue-700/60 dark:bg-blue-950/60 dark:text-blue-200"
            >
                <ShieldCheck class="size-3.5" />
                Portal Internal
            </Badge>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                Selamat datang, {{ userName }}.
            </h1>
            <p class="mt-3 max-w-xl leading-7 text-muted-foreground">
                Anda telah masuk ke ruang kerja internal. Modul ditampilkan
                sesuai capability akun dan setiap akses tetap diverifikasi oleh
                server.
            </p>
        </div>
    </section>
</template>
