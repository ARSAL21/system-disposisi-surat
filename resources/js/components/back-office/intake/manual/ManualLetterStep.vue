<script setup lang="ts">
import { CalendarDays, Hash, MessageSquareText } from '@lucide/vue';
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
    <fieldset class="space-y-6">
        <div>
            <legend class="text-xl font-semibold tracking-tight">
                Apa identitas suratnya?
            </legend>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Salin nomor, tanggal, dan perihal dari dokumen asli agar jejak
                pada buku agenda mudah dicocokkan.
            </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="manual_external_letter_number">
                    Nomor surat pengirim
                    <span class="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                </Label>
                <div class="relative">
                    <Hash
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_external_letter_number"
                        v-model="letterNumber"
                        name="external_letter_number"
                        maxlength="100"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="Contoh: 014/KT-WBR/IX/2026"
                        :aria-invalid="Boolean(errors.external_letter_number)"
                        aria-describedby="manual_external_letter_number_error"
                    />
                </div>
                <InputError
                    id="manual_external_letter_number_error"
                    :message="errors.external_letter_number"
                    role="alert"
                />
            </div>

            <div class="space-y-2">
                <Label for="manual_external_letter_date">
                    Tanggal surat
                    <span class="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                </Label>
                <div class="relative">
                    <CalendarDays
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_external_letter_date"
                        v-model="letterDate"
                        name="external_letter_date"
                        type="date"
                        :max="today"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        :aria-invalid="Boolean(errors.external_letter_date)"
                        aria-describedby="manual_external_letter_date_hint manual_external_letter_date_error"
                    />
                </div>
                <p
                    id="manual_external_letter_date_hint"
                    class="text-xs text-muted-foreground"
                >
                    Tidak boleh melewati hari ini.
                </p>
                <InputError
                    id="manual_external_letter_date_error"
                    :message="errors.external_letter_date"
                    role="alert"
                />
            </div>

            <div class="space-y-2 sm:col-span-2">
                <Label for="manual_subject">
                    Perihal surat
                    <span class="text-destructive" aria-hidden="true">*</span>
                </Label>
                <div class="relative">
                    <MessageSquareText
                        class="pointer-events-none absolute top-3.5 left-4 size-5 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="manual_subject"
                        v-model="subject"
                        name="subject"
                        maxlength="255"
                        class="h-12 rounded-xl bg-background pr-4 pl-12 text-base"
                        placeholder="Tuliskan perihal sesuai surat fisik"
                        :aria-invalid="Boolean(errors.subject)"
                        aria-describedby="manual_subject_hint manual_subject_error"
                    />
                </div>
                <p
                    id="manual_subject_hint"
                    class="text-xs text-muted-foreground"
                >
                    Maksimal 255 karakter.
                </p>
                <InputError
                    id="manual_subject_error"
                    :message="errors.subject"
                    role="alert"
                />
            </div>

            <div class="space-y-2 sm:col-span-2">
                <div class="flex items-baseline justify-between gap-4">
                    <Label for="manual_summary">
                        Ringkasan petugas
                        <span class="font-normal text-muted-foreground">
                            (opsional)
                        </span>
                    </Label>
                    <span class="text-xs text-muted-foreground tabular-nums">
                        {{ summaryLength }}/5000
                    </span>
                </div>
                <textarea
                    id="manual_summary"
                    v-model="summary"
                    name="summary"
                    rows="6"
                    maxlength="5000"
                    class="min-h-36 w-full resize-y rounded-xl border border-input bg-background px-4 py-3 text-base leading-relaxed shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20"
                    placeholder="Ringkas maksud surat tanpa mengubah substansi pengirim..."
                    :aria-invalid="Boolean(errors.summary)"
                    aria-describedby="manual_summary_hint manual_summary_error"
                />
                <p
                    id="manual_summary_hint"
                    class="text-xs text-muted-foreground"
                >
                    Ringkasan membantu Kabag Umum memeriksa konteks surat.
                </p>
                <InputError
                    id="manual_summary_error"
                    :message="errors.summary"
                    role="alert"
                />
            </div>
        </div>
    </fieldset>
</template>
