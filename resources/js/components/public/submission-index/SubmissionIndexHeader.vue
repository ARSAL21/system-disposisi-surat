<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FilePlus2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import publicRoutes from '@/routes/public';
</script>

<template>
    <header
        class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
    >
        <div>
            <p class="text-sm font-semibold text-primary">Surat Saya</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                Kelola pengajuan surat
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Sistem hanya menampilkan pengajuan surat milik akun Anda.
            </p>
        </div>
        <Button class="min-h-11 cursor-pointer rounded-xl px-5" as-child>
            <Link :href="publicRoutes.submissions.create()">
                <FilePlus2 class="size-4" />
                Buat surat
            </Link>
        </Button>
    </header>
</template>
