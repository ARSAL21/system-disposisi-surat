<script setup lang="ts">
import { CircleCheckBig, LockKeyhole } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <Card class="gap-4 rounded-2xl shadow-none">
        <CardHeader>
            <div class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <CircleCheckBig class="size-5" />
                </span>
                <div class="space-y-1">
                    <CardTitle class="text-base"
                        >Fondasi back-office siap digunakan</CardTitle
                    >
                    <p class="text-sm leading-6 text-muted-foreground">
                        Dashboard ini menjadi landasan untuk modul pengelolaan
                        internal berikutnya.
                    </p>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div
                class="flex gap-3 rounded-xl border bg-muted/40 p-4 text-sm text-muted-foreground"
            >
                <LockKeyhole class="mt-0.5 size-4 shrink-0 text-primary" />
                <p class="leading-6">
                    Menu yang tidak terlihat bukan pengganti otorisasi. Setiap
                    operasi tetap diperiksa kembali oleh server.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
