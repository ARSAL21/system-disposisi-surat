<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarDays,
    Fingerprint,
    Landmark,
    ShieldCheck,
    TriangleAlert,
} from '@lucide/vue';

defineProps<{
    verification?: {
        status: 'VALID';
        outgoing_number: string;
        letter_date: string;
        originating_unit_name: string;
        approved_position: string;
        approved_at: string;
        sha256_fingerprint: string;
        replacement: {
            outgoing_number: string;
            letter_date: string | null;
        } | null;
    };
}>();
</script>

<template>
    <Head title="Cek Keaslian Surat" />
    <main
        class="flex flex-1 items-center justify-center bg-muted/20 p-4 sm:p-8"
    >
        <section
            v-if="verification"
            class="w-full max-w-2xl overflow-hidden rounded-[2rem] border bg-background shadow-xl shadow-slate-950/5"
        >
            <div
                class="bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-600 px-6 py-8 text-white sm:px-10"
            >
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-white/15"
                >
                    <BadgeCheck class="size-7" />
                </div>
                <p
                    class="mt-6 text-xs font-semibold tracking-[0.2em] text-indigo-100 uppercase"
                >
                    Verifikasi dokumen
                </p>
                <h1
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Surat terdaftar dan sah
                </h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-indigo-100">
                    QR ini mengonfirmasi nomor dan pengesahan elektronik surat.
                    Isi dokumen tetap tidak ditampilkan pada halaman publik.
                </p>
            </div>
            <div class="grid gap-5 p-6 sm:grid-cols-2 sm:p-10">
                <div
                    v-if="verification.replacement"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:col-span-2 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
                >
                    <p class="flex items-center gap-2 font-semibold">
                        <TriangleAlert class="size-4" /> Surat ini telah
                        digantikan
                    </p>
                    <p class="mt-1 text-sm leading-6">
                        Gunakan surat koreksi nomor
                        {{ verification.replacement.outgoing_number
                        }}<span v-if="verification.replacement.letter_date">
                            tanggal
                            {{ verification.replacement.letter_date }}</span
                        >.
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/25 p-4">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Nomor surat
                    </p>
                    <p class="mt-2 text-lg font-semibold">
                        {{ verification.outgoing_number }}
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/25 p-4">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Tanggal surat
                    </p>
                    <p
                        class="mt-2 flex items-center gap-2 text-lg font-semibold"
                    >
                        <CalendarDays class="size-4 text-indigo-600" />{{
                            verification.letter_date
                        }}
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/25 p-4">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Unit asal
                    </p>
                    <p class="mt-2 flex items-center gap-2 font-semibold">
                        <Landmark class="size-4 text-indigo-600" />{{
                            verification.originating_unit_name
                        }}
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/25 p-4">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Disahkan oleh
                    </p>
                    <p class="mt-2 flex items-center gap-2 font-semibold">
                        <ShieldCheck class="size-4 text-indigo-600" />{{
                            verification.approved_position
                        }}
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/25 p-4 sm:col-span-2">
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Waktu pengesahan
                    </p>
                    <p class="mt-2 font-semibold">
                        {{ verification.approved_at }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 sm:col-span-2 dark:border-indigo-950 dark:bg-indigo-950/20"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Fingerprint PDF hasil pengesahan
                    </p>
                    <p
                        class="mt-2 flex items-start gap-2 font-mono text-xs leading-5 break-all"
                    >
                        <Fingerprint
                            class="mt-0.5 size-4 shrink-0 text-indigo-600"
                        />{{ verification.sha256_fingerprint }}
                    </p>
                </div>
            </div>
        </section>
    </main>
</template>
