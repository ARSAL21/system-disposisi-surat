<script setup lang="ts">
import { Clock3, Mail, MapPinned, Phone, UserRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    errors: Partial<
        Record<
            | 'sender_organization_name'
            | 'contact_name'
            | 'contact_email'
            | 'contact_phone'
            | 'received_at',
            string
        >
    >;
}>();

const organization = defineModel<string>('organization', { required: true });
const contactName = defineModel<string>('contactName', { required: true });
const contactEmail = defineModel<string>('contactEmail', { required: true });
const contactPhone = defineModel<string>('contactPhone', { required: true });
const receivedAt = defineModel<string>('receivedAt', { required: true });
</script>

<template>
    <fieldset class="space-y-6">
        <div>
            <legend class="text-xl font-semibold tracking-tight">
                Dari siapa surat diterima?
            </legend>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Catat informasi sebagaimana tertulis pada surat fisik. Email
                tidak wajib untuk pengirim manual.
            </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2 sm:col-span-2">
                <Label for="manual_sender_organization">
                    Instansi atau organisasi
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <MapPinned
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_sender_organization"
                        v-model="organization"
                        name="sender_organization_name"
                        autocomplete="organization"
                        maxlength="200"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="Contoh: Kelompok Tani Waborobo"
                        :aria-invalid="Boolean(errors.sender_organization_name)"
                        aria-describedby="manual_sender_organization_hint manual_sender_organization_error"
                    />
                </div>
                <p
                    id="manual_sender_organization_hint"
                    class="text-xs text-muted-foreground"
                >
                    Gunakan nama lengkap sesuai kop atau identitas pengirim.
                </p>
                <InputError
                    id="manual_sender_organization_error"
                    :message="errors.sender_organization_name"
                    role="alert"
                />
            </div>

            <div class="space-y-2">
                <Label for="manual_contact_name">
                    Nama kontak
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <UserRound
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_contact_name"
                        v-model="contactName"
                        name="contact_name"
                        autocomplete="name"
                        maxlength="150"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="Nama yang dapat dihubungi"
                        :aria-invalid="Boolean(errors.contact_name)"
                        aria-describedby="manual_contact_name_error"
                    />
                </div>
                <InputError
                    id="manual_contact_name_error"
                    :message="errors.contact_name"
                    role="alert"
                />
            </div>

            <div class="space-y-2">
                <Label for="manual_received_at">
                    Waktu diterima
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <Clock3
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_received_at"
                        v-model="receivedAt"
                        name="received_at"
                        type="datetime-local"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        :aria-invalid="Boolean(errors.received_at)"
                        aria-describedby="manual_received_at_hint manual_received_at_error"
                    />
                </div>
                <p
                    id="manual_received_at_hint"
                    class="text-xs text-muted-foreground"
                >
                    Menggunakan waktu kantor WITA.
                </p>
                <InputError
                    id="manual_received_at_error"
                    :message="errors.received_at"
                    role="alert"
                />
            </div>

            <div class="space-y-2">
                <Label for="manual_contact_email">
                    Email
                    <span class="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                </Label>
                <div class="relative">
                    <Mail
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_contact_email"
                        v-model="contactEmail"
                        name="contact_email"
                        type="email"
                        autocomplete="email"
                        maxlength="255"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="nama@instansi.go.id"
                        :aria-invalid="Boolean(errors.contact_email)"
                        aria-describedby="manual_contact_email_hint manual_contact_email_error"
                    />
                </div>
                <p
                    id="manual_contact_email_hint"
                    class="text-xs text-muted-foreground"
                >
                    Kosongkan jika tidak tercantum pada surat.
                </p>
                <InputError
                    id="manual_contact_email_error"
                    :message="errors.contact_email"
                    role="alert"
                />
            </div>

            <div class="space-y-2">
                <Label for="manual_contact_phone">
                    Nomor telepon
                    <span class="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                </Label>
                <div class="relative">
                    <Phone
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_contact_phone"
                        v-model="contactPhone"
                        name="contact_phone"
                        type="tel"
                        autocomplete="tel"
                        inputmode="tel"
                        maxlength="30"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="Contoh: 0812 3456 7890"
                        :aria-invalid="Boolean(errors.contact_phone)"
                        aria-describedby="manual_contact_phone_error"
                    />
                </div>
                <InputError
                    id="manual_contact_phone_error"
                    :message="errors.contact_phone"
                    role="alert"
                />
            </div>
        </div>
    </fieldset>
</template>
