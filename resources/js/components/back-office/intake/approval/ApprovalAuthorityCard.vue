<script setup lang="ts">
import { ArrowDown, ClipboardCheck, Landmark, ShieldCheck } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
    <Card class="border-violet-200 shadow-sm dark:border-violet-900">
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <ShieldCheck
                    class="size-5 text-violet-600 dark:text-violet-300"
                    aria-hidden="true"
                />
                Batas kewenangan
            </CardTitle>
        </CardHeader>
        <CardContent>
            <ol class="space-y-3" aria-label="Alur persetujuan surat">
                <li
                    class="rounded-2xl border border-emerald-200 bg-emerald-50/65 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"
                >
                    <div class="flex gap-3">
                        <ClipboardCheck
                            class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="text-sm font-semibold">
                                Pemeriksaan petugas selesai
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Identitas, data surat, dan PDF telah diperiksa.
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
                        <Landmark
                            class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="text-sm font-semibold">
                                Keputusan Kepala Bagian Umum
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                Kembalikan, tolak, atau registrasikan secara
                                resmi.
                            </p>
                        </div>
                    </div>
                </li>
            </ol>
            <p
                class="mt-4 rounded-xl bg-muted/60 p-3 text-xs leading-5 text-muted-foreground"
            >
                Registrasi belum merupakan disposisi. Penerusan kepada Sekda
                atau Wali Kota dilakukan pada tahap berikutnya.
            </p>
        </CardContent>
    </Card>
</template>
