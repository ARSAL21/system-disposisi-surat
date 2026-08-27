<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    today: string;
    errors: Partial<
        Record<
            | 'external_letter_number'
            | 'external_letter_date'
            | 'subject'
            | 'summary',
            string
        >
    >;
}>();

const letterNumber = defineModel<string>('letterNumber', { required: true });
const letterDate = defineModel<string>('letterDate', { required: true });
const subject = defineModel<string>('subject', { required: true });
const summary = defineModel<string>('summary', { required: true });
const summaryLength = computed(() => summary.value.length);
</script>

<template>
    <fieldset class="space-y-5">
        <legend class="text-lg font-semibold tracking-tight">
            Informasi surat
        </legend>
        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
            Salin informasi sesuai dokumen asli agar pemeriksaan administratif
            lebih mudah dilakukan.
        </p>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="external_letter_number"
                    >Nomor surat
                    <span class="font-normal text-muted-foreground"
                        >(opsional)</span
                    ></Label
                >
                <Input
                    id="external_letter_number"
                    v-model="letterNumber"
                    name="external_letter_number"
                    maxlength="100"
                    class="h-12 rounded-xl bg-background px-4 text-base"
                    placeholder="Contoh: 001/ORG/VIII/2026"
                    :aria-invalid="Boolean(errors.external_letter_number)"
                    aria-describedby="external_letter_number_error"
                />
                <InputError
                    id="external_letter_number_error"
                    :message="errors.external_letter_number"
                    role="alert"
                />
            </div>
            <div class="space-y-2">
                <Label for="external_letter_date"
                    >Tanggal surat
                    <span class="font-normal text-muted-foreground"
                        >(opsional)</span
                    ></Label
                >
                <Input
                    id="external_letter_date"
                    v-model="letterDate"
                    name="external_letter_date"
                    type="date"
                    :max="today"
                    class="h-12 rounded-xl bg-background px-4 text-base"
                    :aria-invalid="Boolean(errors.external_letter_date)"
                    aria-describedby="external_letter_date_hint external_letter_date_error"
                />
                <p
                    id="external_letter_date_hint"
                    class="text-xs text-muted-foreground"
                >
                    Tanggal tidak boleh melewati hari ini.
                </p>
                <InputError
                    id="external_letter_date_error"
                    :message="errors.external_letter_date"
                    role="alert"
                />
            </div>
            <div class="space-y-2 sm:col-span-2">
                <Label for="subject"
                    >Perihal surat
                    <span class="text-destructive" aria-hidden="true"
                        >*</span
                    ></Label
                >
                <Input
                    id="subject"
                    v-model="subject"
                    name="subject"
                    maxlength="255"
                    class="h-12 rounded-xl bg-background px-4 text-base"
                    placeholder="Tuliskan perihal sesuai surat"
                    :aria-invalid="Boolean(errors.subject)"
                    aria-describedby="subject_hint subject_error"
                />
                <p id="subject_hint" class="text-xs text-muted-foreground">
                    Maksimal 255 karakter.
                </p>
                <InputError
                    id="subject_error"
                    :message="errors.subject"
                    role="alert"
                />
            </div>
            <div class="space-y-2 sm:col-span-2">
                <div class="flex items-baseline justify-between gap-4">
                    <Label for="summary"
                        >Ringkasan
                        <span class="font-normal text-muted-foreground"
                            >(opsional)</span
                        ></Label
                    >
                    <span class="text-xs text-muted-foreground tabular-nums"
                        >{{ summaryLength }}/5000</span
                    >
                </div>
                <textarea
                    id="summary"
                    v-model="summary"
                    name="summary"
                    rows="6"
                    maxlength="5000"
                    class="min-h-36 w-full resize-y rounded-xl border border-input bg-background px-4 py-3 text-base leading-relaxed shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20"
                    placeholder="Berikan konteks singkat agar maksud surat mudah dipahami"
                    :aria-invalid="Boolean(errors.summary)"
                    aria-describedby="summary_error"
                />
                <InputError
                    id="summary_error"
                    :message="errors.summary"
                    role="alert"
                />
            </div>
        </div>
    </fieldset>
</template>
