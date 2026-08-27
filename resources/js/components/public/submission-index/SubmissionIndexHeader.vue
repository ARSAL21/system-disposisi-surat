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
                Kelola submission surat
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Hanya submission milik akun Anda yang ditampilkan oleh server.
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
