<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, LogOut, MailCheck, Send } from '@lucide/vue';
import TextLink from '@/components/TextLink.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verifikasi Alamat Email',
        description:
            'Satu langkah lagi! Silakan periksa kotak masuk email Anda dan klik tautan verifikasi yang telah kami kirimkan.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Verifikasi Alamat Email" />

    <div class="space-y-6">
        <Alert
            v-if="status === 'verification-link-sent'"
            class="border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
        >
            <CheckCircle2 class="size-4" />
            <AlertDescription class="font-medium">
                Tautan verifikasi baru telah berhasil dikirim ke alamat email Anda.
            </AlertDescription>
        </Alert>

        <div
            class="rounded-2xl border border-border/70 bg-muted/30 p-4 text-center text-xs leading-relaxed text-muted-foreground"
        >
            <div
                class="mx-auto mb-2.5 flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-400"
            >
                <MailCheck class="size-5" />
            </div>
            <p>
                Belum menerima email? Periksa folder spam atau klik tombol di bawah untuk meminta pengiriman ulang tautan verifikasi.
            </p>
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-4 text-center"
            v-slot="{ processing }"
        >
            <Button
                type="submit"
                class="h-12 w-full gap-2 rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 font-semibold text-white shadow-lg shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500 hover:shadow-emerald-600/35 active:scale-[0.99] dark:from-emerald-500 dark:via-teal-600 dark:to-cyan-600"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                <Send v-else class="size-4" />
                <span>{{
                    processing
                        ? 'Mengirim ulang...'
                        : 'Kirim Ulang Email Verifikasi'
                }}</span>
            </Button>

            <div class="pt-2">
                <TextLink
                    :href="logout.url()"
                    as="button"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <LogOut class="size-3.5" />
                    <span>Keluar dari sesi ini</span>
                </TextLink>
            </div>
        </Form>
    </div>
</template>
