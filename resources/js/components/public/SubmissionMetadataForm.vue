<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Save } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import type { LetterSubmission } from '@/types';

const props = defineProps<{
    submission?: LetterSubmission;
}>();

const page = usePage();
const formRoot = ref<HTMLFormElement | null>(null);
const isEditing = computed(() => Boolean(props.submission));
const summaryLength = computed(() => form.summary?.length ?? 0);

const today = (() => {
    const current = new Date();
    const local = new Date(
        current.getTime() - current.getTimezoneOffset() * 60_000,
    );

    return local.toISOString().slice(0, 10);
})();

const form = useForm({
    sender_organization_name: props.submission?.sender_organization_name ?? '',
    contact_phone: props.submission?.contact_phone ?? '',
    external_letter_number: props.submission?.external_letter_number ?? '',
    external_letter_date: props.submission?.external_letter_date ?? '',
    subject: props.submission?.subject ?? '',
    summary: props.submission?.summary ?? '',
});

function focusFirstError(): void {
    void nextTick(() => {
        formRoot.value
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus();
    });
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onError: focusFirstError,
    };

    if (props.submission) {
        form.patch(
            publicSubmissionRoutes.update(props.submission.public_id),
            options,
        );

        return;
    }

    form.post(publicSubmissionRoutes.store, options);
}
</script>

<template>
    <form ref="formRoot" class="space-y-8" novalidate @submit.prevent="submit">
        <div
            v-if="Object.keys(form.errors).length > 1"
            class="rounded-2xl border border-destructive/30 bg-destructive/8 p-4 text-sm text-destructive"
            role="alert"
        >
            Beberapa informasi perlu diperiksa kembali. Detail kesalahan
            tersedia di bawah setiap kolom.
        </div>

        <fieldset class="space-y-5">
            <legend class="text-lg font-semibold tracking-tight">
                Identitas pengirim
            </legend>
            <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
                Nama dan email berasal dari akun terverifikasi. Keduanya tidak
                dapat diganti melalui form submission.
            </p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border bg-muted/45 px-4 py-3">
                    <span class="text-xs font-medium text-muted-foreground"
                        >Nama kontak</span
                    >
                    <p class="mt-1 text-sm font-semibold break-words">
                        {{
                            submission?.contact_name ||
                            page.props.auth.user.name
                        }}
                    </p>
                </div>
                <div class="rounded-2xl border bg-muted/45 px-4 py-3">
                    <span class="text-xs font-medium text-muted-foreground"
                        >Email terverifikasi</span
                    >
                    <p class="mt-1 text-sm font-semibold break-all">
                        {{
                            submission?.contact_email ||
                            page.props.auth.user.email
                        }}
                    </p>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <Label for="sender_organization_name">
                        Nama instansi atau organisasi
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="sender_organization_name"
                        v-model="form.sender_organization_name"
                        name="sender_organization_name"
                        type="text"
                        autocomplete="organization"
                        maxlength="200"
                        class="h-12 rounded-xl bg-background px-4 text-base"
                        placeholder="Contoh: Universitas, perusahaan, atau organisasi"
                        :aria-invalid="
                            Boolean(form.errors.sender_organization_name)
                        "
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
                        :message="form.errors.sender_organization_name"
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
                        v-model="form.contact_phone"
                        name="contact_phone"
                        type="tel"
                        autocomplete="tel"
                        inputmode="tel"
                        maxlength="30"
                        class="h-12 rounded-xl bg-background px-4 text-base"
                        placeholder="Contoh: 0812 3456 7890"
                        :aria-invalid="Boolean(form.errors.contact_phone)"
                        aria-describedby="contact_phone_error"
                    />
                    <InputError
                        id="contact_phone_error"
                        :message="form.errors.contact_phone"
                        role="alert"
                    />
                </div>
            </div>
        </fieldset>

        <div class="h-px bg-border" />

        <fieldset class="space-y-5">
            <legend class="text-lg font-semibold tracking-tight">
                Informasi surat
            </legend>
            <p class="max-w-2xl text-sm leading-relaxed text-muted-foreground">
                Salin informasi sesuai dokumen asli agar pemeriksaan
                administratif lebih mudah dilakukan.
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="external_letter_number">
                        Nomor surat
                        <span class="font-normal text-muted-foreground"
                            >(opsional)</span
                        >
                    </Label>
                    <Input
                        id="external_letter_number"
                        v-model="form.external_letter_number"
                        name="external_letter_number"
                        maxlength="100"
                        class="h-12 rounded-xl bg-background px-4 text-base"
                        placeholder="Contoh: 001/ORG/VIII/2026"
                        :aria-invalid="
                            Boolean(form.errors.external_letter_number)
                        "
                        aria-describedby="external_letter_number_error"
                    />
                    <InputError
                        id="external_letter_number_error"
                        :message="form.errors.external_letter_number"
                        role="alert"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="external_letter_date">
                        Tanggal surat
                        <span class="font-normal text-muted-foreground"
                            >(opsional)</span
                        >
                    </Label>
                    <Input
                        id="external_letter_date"
                        v-model="form.external_letter_date"
                        name="external_letter_date"
                        type="date"
                        :max="today"
                        class="h-12 rounded-xl bg-background px-4 text-base"
                        :aria-invalid="
                            Boolean(form.errors.external_letter_date)
                        "
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
                        :message="form.errors.external_letter_date"
                        role="alert"
                    />
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="subject">
                        Perihal surat
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </Label>
                    <Input
                        id="subject"
                        v-model="form.subject"
                        name="subject"
                        maxlength="255"
                        class="h-12 rounded-xl bg-background px-4 text-base"
                        placeholder="Tuliskan perihal sesuai surat"
                        :aria-invalid="Boolean(form.errors.subject)"
                        aria-describedby="subject_hint subject_error"
                    />
                    <p id="subject_hint" class="text-xs text-muted-foreground">
                        Maksimal 255 karakter.
                    </p>
                    <InputError
                        id="subject_error"
                        :message="form.errors.subject"
                        role="alert"
                    />
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <div class="flex items-baseline justify-between gap-4">
                        <Label for="summary">
                            Ringkasan
                            <span class="font-normal text-muted-foreground"
                                >(opsional)</span
                            >
                        </Label>
                        <span
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ summaryLength }}/5000
                        </span>
                    </div>
                    <textarea
                        id="summary"
                        v-model="form.summary"
                        name="summary"
                        rows="6"
                        maxlength="5000"
                        class="min-h-36 w-full resize-y rounded-xl border border-input bg-background px-4 py-3 text-base leading-relaxed shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30 aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20"
                        placeholder="Berikan konteks singkat agar maksud surat mudah dipahami"
                        :aria-invalid="Boolean(form.errors.summary)"
                        aria-describedby="summary_error"
                    />
                    <InputError
                        id="summary_error"
                        :message="form.errors.summary"
                        role="alert"
                    />
                </div>
            </div>
        </fieldset>

        <div
            class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <Button
                variant="ghost"
                size="lg"
                class="min-h-11 cursor-pointer rounded-xl"
                as-child
            >
                <Link
                    :href="
                        submission
                            ? publicSubmissionRoutes.show(submission.public_id)
                            : publicSubmissionRoutes.index
                    "
                >
                    <ArrowLeft class="size-4" />
                    Batal
                </Link>
            </Button>
            <Button
                type="submit"
                size="lg"
                class="min-h-11 cursor-pointer rounded-xl px-6"
                :disabled="form.processing"
            >
                <Save v-if="isEditing" class="size-4" />
                <ArrowRight v-else class="size-4" />
                {{
                    form.processing
                        ? 'Menyimpan…'
                        : isEditing
                          ? 'Simpan perubahan'
                          : 'Simpan dan lanjutkan'
                }}
            </Button>
        </div>
    </form>
</template>
