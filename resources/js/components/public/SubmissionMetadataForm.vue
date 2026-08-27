<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Save } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import SubmissionLetterFields from '@/components/public/submission-form/SubmissionLetterFields.vue';
import SubmissionSenderFields from '@/components/public/submission-form/SubmissionSenderFields.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import type { LetterSubmission } from '@/types';

const props = defineProps<{ submission?: LetterSubmission }>();
const page = usePage();
const formRoot = ref<HTMLFormElement | null>(null);
const isEditing = computed(() => Boolean(props.submission));
const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60_000)
    .toISOString()
    .slice(0, 10);

const form = useForm({
    sender_organization_name: props.submission?.sender_organization_name ?? '',
    contact_phone: props.submission?.contact_phone ?? '',
    external_letter_number: props.submission?.external_letter_number ?? '',
    external_letter_date: props.submission?.external_letter_date ?? '',
    subject: props.submission?.subject ?? '',
    summary: props.submission?.summary ?? '',
});

function focusFirstError(): void {
    void nextTick(() =>
        formRoot.value
            ?.querySelector<HTMLElement>('[aria-invalid="true"]')
            ?.focus(),
    );
}

function submit(): void {
    const options = { preserveScroll: true, onError: focusFirstError };

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
    <form ref="formRoot" class="space-y-7" novalidate @submit.prevent="submit">
        <div
            v-if="Object.keys(form.errors).length > 1"
            class="rounded-xl border border-destructive/30 bg-destructive/8 p-4 text-sm text-destructive"
            role="alert"
        >
            Beberapa informasi perlu diperiksa kembali. Detail kesalahan
            tersedia di bawah setiap kolom.
        </div>
        <SubmissionSenderFields
            v-model:organization="form.sender_organization_name"
            v-model:contact-phone="form.contact_phone"
            :contact-name="
                submission?.contact_name || page.props.auth.user.name
            "
            :contact-email="
                submission?.contact_email || page.props.auth.user.email
            "
            :errors="form.errors"
        />
        <div class="h-px bg-border" />
        <SubmissionLetterFields
            v-model:letter-number="form.external_letter_number"
            v-model:letter-date="form.external_letter_date"
            v-model:subject="form.subject"
            v-model:summary="form.summary"
            :today="today"
            :errors="form.errors"
        />
        <div
            class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <Button
                variant="ghost"
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
                    <ArrowLeft class="size-4" />Batal
                </Link>
            </Button>
            <Button
                type="submit"
                class="min-h-11 cursor-pointer rounded-xl px-5"
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
