<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FilePlus2, Inbox } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import publicRoutes from '@/routes/public';
</script>

<template>
    <section
        class="flex min-h-96 flex-col items-center justify-center rounded-2xl border border-dashed bg-card px-6 py-14 text-center"
    >
        <span
            class="flex size-14 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"
        >
            <Inbox class="size-6" />
        </span>
        <h2 class="mt-5 text-xl font-semibold tracking-tight">
            Belum ada submission
        </h2>
        <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
            Buat draft pertama Anda. PDF dapat ditambahkan setelah informasi
            surat tersimpan.
        </p>
        <Button class="mt-6 min-h-11 cursor-pointer rounded-xl px-5" as-child>
            <Link :href="publicRoutes.submissions.create()">
                <FilePlus2 class="size-4" />Buat submission pertama
            </Link>
        </Button>
    </section>
</template>
