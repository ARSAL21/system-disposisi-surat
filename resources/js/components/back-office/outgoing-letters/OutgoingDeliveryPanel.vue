<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Check,
    Clock3,
    Mail,
    MapPin,
    Package,
    RotateCcw,
    Send,
    Truck,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { OutgoingDeliveryMethod, OutgoingLetterDetail } from '@/types';

const props = withDefaults(
    defineProps<{ letter: OutgoingLetterDetail; preview?: boolean }>(),
    { preview: false },
);
const emit = defineEmits<{ delivered: [] }>();

const methods: Array<{
    value: OutgoingDeliveryMethod;
    label: string;
    description: string;
    icon: Component;
}> = [
    {
        value: 'EMAIL',
        label: 'Email',
        description: 'Tautan unduh aman',
        icon: Mail,
    },
    {
        value: 'IN_PERSON',
        label: 'Serah terima langsung',
        description: 'Dicatat dengan penerima',
        icon: MapPin,
    },
    {
        value: 'POSTAL',
        label: 'Pos',
        description: 'Dengan nomor resi',
        icon: Package,
    },
    {
        value: 'COURIER',
        label: 'Kurir',
        description: 'Pengiriman berjejak',
        icon: Truck,
    },
    {
        value: 'OTHER',
        label: 'Lainnya',
        description: 'Metode khusus',
        icon: Send,
    },
];

const selectedMethod = ref<OutgoingDeliveryMethod>('EMAIL');
const previewSent = ref(false);
const form = useForm({
    delivery_method: 'EMAIL' as OutgoingDeliveryMethod,
    recipient_name: '',
    delivered_at: '',
    tracking_number: '',
    delivery_note: '',
});

const canDeliver = computed(
    () =>
        props.letter.capabilities.can_deliver &&
        Boolean(props.letter.routes.deliver),
);
const hasDelivered = computed(
    () =>
        props.letter.status === 'DELIVERED' ||
        Boolean(props.letter.delivery.delivered_at) ||
        previewSent.value,
);
const email = computed(() => props.letter.delivery.email ?? null);

function chooseMethod(method: OutgoingDeliveryMethod): void {
    selectedMethod.value = method;
    form.delivery_method = method;
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function statusLabel(
    status: NonNullable<
        NonNullable<OutgoingLetterDetail['delivery']['email']>['status']
    >,
): string {
    return {
        NOT_SENT: 'Belum dikirim',
        SENT: 'Tautan aktif',
        EXPIRED: 'Tautan kedaluwarsa',
        REVOKED: 'Tautan dicabut',
    }[status];
}

function submit(): void {
    if (!canDeliver.value || !props.letter.routes.deliver) {
        return;
    }

    if (props.preview) {
        previewSent.value = true;
        toast.success('Simulasi pengiriman berhasil dicatat.');
        emit('delivered');

        return;
    }

    form.post(props.letter.routes.deliver, {
        preserveScroll: true,
        onSuccess: () => emit('delivered'),
    });
}

function emailAction(route: string | null, message: string): void {
    if (!route) {
        return;
    }

    if (props.preview) {
        toast.success(`Simulasi: ${message}.`);

        return;
    }

    form.post(route, {
        preserveScroll: true,
        onSuccess: () => toast.success(`${message}.`),
    });
}
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader class="border-b bg-muted/20 pb-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <CardTitle class="flex items-center gap-2 text-base"
                        ><Send class="size-4 text-indigo-600" /> Kirim
                        surat</CardTitle
                    >
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Pilih cara pengiriman dan simpan buktinya.
                    </p>
                </div>
                <Badge
                    v-if="hasDelivered"
                    class="rounded-full border-emerald-200 bg-emerald-50 text-emerald-700"
                    ><Check class="size-3.5" /> Terkirim</Badge
                >
            </div>
        </CardHeader>
        <CardContent class="space-y-5 p-5">
            <Alert
                v-if="hasDelivered"
                class="border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/25"
                ><Check class="size-4 text-emerald-600" /><AlertTitle
                    >Pengiriman tercatat</AlertTitle
                ><AlertDescription>{{
                    letter.delivery.method === 'EMAIL'
                        ? 'Tautan unduh sudah dikirim tanpa melampirkan PDF.'
                        : 'Bukti penyerahan tersimpan dan surat tidak dapat diubah.'
                }}</AlertDescription></Alert
            >

            <div
                class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5"
                role="radiogroup"
                aria-label="Metode pengiriman"
            >
                <button
                    v-for="method in methods"
                    :key="method.value"
                    type="button"
                    :disabled="hasDelivered || !canDeliver"
                    class="group rounded-2xl border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                    :class="
                        selectedMethod === method.value
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-950 shadow-sm dark:border-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-100'
                            : 'bg-background hover:border-indigo-300 hover:bg-muted/25'
                    "
                    role="radio"
                    :aria-checked="selectedMethod === method.value"
                    @click="chooseMethod(method.value)"
                >
                    <component
                        :is="method.icon"
                        class="size-4"
                        :class="
                            selectedMethod === method.value
                                ? 'text-indigo-600'
                                : 'text-muted-foreground'
                        "
                    /><span
                        class="mt-3 block text-xs leading-4 font-semibold"
                        >{{ method.label }}</span
                    ><span
                        class="mt-1 block text-[11px] leading-4 text-muted-foreground"
                        >{{ method.description }}</span
                    >
                </button>
            </div>

            <div
                v-if="selectedMethod === 'EMAIL'"
                class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900 dark:bg-sky-950/25"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.14em] text-sky-700 uppercase dark:text-sky-300"
                        >
                            Tautan unduh aman
                        </p>
                        <p class="mt-1 text-sm font-semibold">
                            {{
                                letter.delivery.recipient_email ||
                                'Alamat penerima akan diambil dari data pemohon'
                            }}
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        class="rounded-full border-sky-300 text-sky-700 dark:border-sky-800 dark:text-sky-300"
                        >{{ statusLabel(email?.status ?? 'NOT_SENT') }}</Badge
                    >
                </div>
                <div
                    class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2"
                >
                    <p class="flex items-center gap-2">
                        <Clock3 class="size-3.5" />Dikirim:
                        {{ formatDate(email?.sent_at) }}
                    </p>
                    <p class="flex items-center gap-2">
                        <Clock3 class="size-3.5" />Berlaku sampai:
                        {{ formatDate(email?.expires_at) }}
                    </p>
                </div>
                <div
                    v-if="
                        hasDelivered &&
                        (email?.status === 'SENT' ||
                            email?.status === 'EXPIRED' ||
                            email?.status === 'REVOKED')
                    "
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <Button
                        v-if="
                            letter.capabilities.can_resend_delivery_email &&
                            letter.routes.resend_delivery_email
                        "
                        type="button"
                        variant="outline"
                        size="sm"
                        class="rounded-xl"
                        @click="
                            emailAction(
                                letter.routes.resend_delivery_email,
                                'Tautan dikirim ulang',
                            )
                        "
                        ><RotateCcw class="size-3.5" /> Kirim ulang</Button
                    ><Button
                        v-if="
                            letter.capabilities.can_revoke_delivery_email &&
                            letter.routes.revoke_delivery_email
                        "
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="rounded-xl text-destructive hover:text-destructive"
                        @click="
                            emailAction(
                                letter.routes.revoke_delivery_email,
                                'Tautan dicabut',
                            )
                        "
                        ><X class="size-3.5" /> Cabut tautan</Button
                    >
                </div>
            </div>

            <form
                v-if="!hasDelivered && canDeliver"
                class="space-y-4 border-t pt-4"
                @submit.prevent="submit"
            >
                <template v-if="selectedMethod !== 'EMAIL'"
                    ><div class="space-y-2">
                        <Label for="outgoing-delivery-recipient"
                            >Nama penerima</Label
                        ><Input
                            id="outgoing-delivery-recipient"
                            v-model="form.recipient_name"
                            required
                            minlength="3"
                            maxlength="150"
                            placeholder="Nama penerima surat"
                        /><InputError :message="form.errors.recipient_name" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="outgoing-delivery-time"
                                >Waktu penyerahan</Label
                            ><Input
                                id="outgoing-delivery-time"
                                v-model="form.delivered_at"
                                required
                                type="datetime-local"
                            /><InputError :message="form.errors.delivered_at" />
                        </div>
                        <div class="space-y-2">
                            <Label for="outgoing-delivery-tracking"
                                >Nomor resi/referensi</Label
                            ><Input
                                id="outgoing-delivery-tracking"
                                v-model="form.tracking_number"
                                maxlength="100"
                                placeholder="Opsional"
                            />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="outgoing-delivery-note"
                            >Catatan penyerahan</Label
                        ><textarea
                            id="outgoing-delivery-note"
                            v-model="form.delivery_note"
                            maxlength="2000"
                            rows="3"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Keterangan tambahan (opsional)"
                        /></div
                ></template>
                <Button
                    type="submit"
                    class="min-h-11 w-full rounded-xl"
                    :disabled="form.processing"
                    ><Spinner v-if="form.processing" /><Send class="size-4" />{{
                        selectedMethod === 'EMAIL'
                            ? 'Kirim tautan unduh'
                            : 'Catat pengiriman'
                    }}</Button
                >
            </form>
            <p
                v-else-if="!hasDelivered"
                class="rounded-2xl border border-dashed p-4 text-center text-sm text-muted-foreground"
            >
                Panel pengiriman akan aktif setelah dokumen dinyatakan siap
                dikirim.
            </p>
        </CardContent>
    </Card>
</template>
