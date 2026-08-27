<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    contactName: string;
    contactEmail: string;
    errors: Partial<
        Record<'sender_organization_name' | 'contact_phone', string>
    >;
}>();

const organization = defineModel<string>('organization', { required: true });
const contactPhone = defineModel<string>('contactPhone', { required: true });
</script>

<template>
    <fieldset class="space-y-5">
        <legend class="text-lg font-semibold tracking-tight">
            Identitas pengirim
        </legend>
        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
            Nama dan email berasal dari akun terverifikasi dan tidak dapat
            diganti melalui form ini.
        </p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border bg-muted/50 px-4 py-3">
                <span class="text-xs font-medium text-muted-foreground"
                    >Nama kontak</span
                >
                <p class="mt-1 text-sm font-semibold break-words">
                    {{ contactName }}
                </p>
            </div>
            <div class="rounded-xl border bg-muted/50 px-4 py-3">
                <span class="text-xs font-medium text-muted-foreground"
                    >Email terverifikasi</span
                >
                <p class="mt-1 text-sm font-semibold break-all">
                    {{ contactEmail }}
                </p>
            </div>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2 sm:col-span-2">
                <Label for="sender_organization_name">
                    Nama instansi atau organisasi
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <Input
                    id="sender_organization_name"
                    v-model="organization"
                    name="sender_organization_name"
                    type="text"
                    autocomplete="organization"
                    maxlength="200"
                    class="h-12 rounded-xl bg-background px-4 text-base"
                    placeholder="Contoh: Universitas, perusahaan, atau organisasi"
                    :aria-invalid="Boolean(errors.sender_organization_name)"
                    aria-describedby="sender_organization_name_hint sender_organization_name_error"
                />
                <p
                    id="sender_organization_name_hint"
                    class="text-xs text-muted-foreground"
                >
                    Maksimal 200 karakter.
                </p>
                <InputError
                    id="sender_organization_name_error"
                    :message="errors.sender_organization_name"
                    role="alert"
                />
            </div>
            <div class="space-y-2">
                <Label for="contact_phone"
                    >Nomor telepon
                    <span class="font-normal text-muted-foreground"
                        >(opsional)</span
                    ></Label
                >
                <Input
                    id="contact_phone"
                    v-model="contactPhone"
                    name="contact_phone"
                    type="tel"
                    autocomplete="tel"
                    inputmode="tel"
                    maxlength="30"
                    class="h-12 rounded-xl bg-background px-4 text-base"
                    placeholder="Contoh: 0812 3456 7890"
                    :aria-invalid="Boolean(errors.contact_phone)"
                    aria-describedby="contact_phone_error"
                />
                <InputError
                    id="contact_phone_error"
                    :message="errors.contact_phone"
                    role="alert"
                />
            </div>
        </div>
    </fieldset>
</template>
