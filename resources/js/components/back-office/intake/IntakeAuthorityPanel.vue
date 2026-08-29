<script setup lang="ts">
import { ArrowDown, CheckCircle2, ClipboardCheck, Scale } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <Card class="border-violet-200 shadow-sm dark:border-violet-900">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Scale
                    class="size-5 text-violet-600 dark:text-violet-300"
                    aria-hidden="true"
                />
                Batas kewenangan
            </CardTitle>
        </CardHeader>
        <CardContent>
            <ol
                class="space-y-3"
                aria-label="Alur kewenangan pemeriksaan surat"
            >
                <li
                    class="rounded-2xl border border-blue-200 bg-blue-50/65 p-4 dark:border-blue-900 dark:bg-blue-950/20"
                >
                    <div class="flex gap-3">
                        <ClipboardCheck
                            class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="text-sm font-semibold">
                                1. Petugas Bagian Umum
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Memeriksa kelengkapan, meminta koreksi, dan
                                menyiapkan rancangan pencatatan resmi.
                            </p>
                        </div>
                    </div>
                </li>
                <li
                    class="flex justify-center text-muted-foreground"
                    aria-hidden="true"
                >
                    <ArrowDown class="size-4" />
                </li>
                <li
                    class="rounded-2xl border border-violet-200 bg-violet-50/65 p-4 dark:border-violet-900 dark:bg-violet-950/20"
                >
                    <div class="flex gap-3">
                        <CheckCircle2
                            class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="text-sm font-semibold">
                                2. Kepala Bagian Umum
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Memberi keputusan administratif resmi:
                                kembalikan, tolak, atau registrasikan.
                            </p>
                        </div>
                    </div>
                </li>
            </ol>
            <p
                class="mt-4 rounded-xl bg-muted/60 p-3 text-xs leading-5 text-muted-foreground"
            >
                Penerusan kepada Sekda adalah proses terpisah setelah surat
                resmi terdaftar.
            </p>
        </CardContent>
    </Card>
</template>
