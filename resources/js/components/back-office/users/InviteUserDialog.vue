<script setup lang="ts">
import {
    Building2,
    CheckCircle2,
    Globe,
    Mail,
    Send,
    Shield,
    UserPlus,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type {
    InviteUserPayload,
    UserAccountType,
} from '@/types/user-management';

const props = defineProps<{
    open: boolean;
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    submit: [payload: InviteUserPayload];
}>();

const accountType = ref<UserAccountType>('INTERNAL');
const name = ref('');
const email = ref('');
const errors = ref<Record<string, string>>({});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            name.value = '';
            email.value = '';
            accountType.value = 'INTERNAL';
            errors.value = {};
        }
    },
);

const isEmailValid = computed(() => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
});

function validateForm(): boolean {
    errors.value = {};
    const trimmedName = name.value.trim();
    const trimmedEmail = email.value.trim();

    if (!trimmedName) {
        errors.value.name = 'Nama lengkap calon pengguna wajib diisi.';
    } else if (trimmedName.length < 3) {
        errors.value.name = 'Nama minimal 3 karakter.';
    }

    if (!trimmedEmail) {
        errors.value.email = 'Alamat email wajib diisi.';
    } else if (!isEmailValid.value) {
        errors.value.email = 'Format alamat email tidak valid.';
    }

    return Object.keys(errors.value).length === 0;
}

function handleSubmit(): void {
    if (props.processing) {
        return;
    }

    if (!validateForm()) {
        return;
    }

    emit('submit', {
        name: name.value.trim(),
        email: email.value.trim().toLowerCase(),
        account_type: accountType.value,
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] max-w-xl overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <UserPlus class="size-5" />
                    </span>
                    <div>
                        <DialogTitle class="text-xl font-bold tracking-tight">
                            Undang Pengguna Baru
                        </DialogTitle>
                        <DialogDescription
                            class="text-sm text-muted-foreground"
                        >
                            Kirimkan tautan aktivasi akun resmi ke alamat email
                            penerima.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form class="space-y-5 py-2" @submit.prevent="handleSubmit">
                <!-- Tipe Akun Selector -->
                <div>
                    <label
                        class="mb-2 block text-sm font-semibold text-foreground"
                    >
                        Pilih Tipe Akun
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            class="relative flex cursor-pointer flex-col items-start rounded-xl border-2 p-3.5 text-left transition-all"
                            :class="
                                accountType === 'INTERNAL'
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-border/70 text-muted-foreground hover:border-border hover:bg-muted/40'
                            "
                            @click="accountType = 'INTERNAL'"
                        >
                            <div
                                class="mb-1.5 flex w-full items-center justify-between"
                            >
                                <span
                                    class="flex items-center gap-2 text-sm font-semibold"
                                >
                                    <Building2 class="size-4" />
                                    Akun Internal
                                </span>
                                <CheckCircle2
                                    v-if="accountType === 'INTERNAL'"
                                    class="size-4 text-primary"
                                />
                            </div>
                            <p
                                class="text-xs leading-relaxed text-muted-foreground"
                            >
                                Pegawai, staf, atau pejabat di lingkungan
                                Pemerintah Kota.
                            </p>
                        </button>

                        <button
                            type="button"
                            class="relative flex cursor-pointer flex-col items-start rounded-xl border-2 p-3.5 text-left transition-all"
                            :class="
                                accountType === 'PUBLIC'
                                    ? 'border-primary bg-primary/5 text-primary'
                                    : 'border-border/70 text-muted-foreground hover:border-border hover:bg-muted/40'
                            "
                            @click="accountType = 'PUBLIC'"
                        >
                            <div
                                class="mb-1.5 flex w-full items-center justify-between"
                            >
                                <span
                                    class="flex items-center gap-2 text-sm font-semibold"
                                >
                                    <Globe class="size-4" />
                                    Akun Publik
                                </span>
                                <CheckCircle2
                                    v-if="accountType === 'PUBLIC'"
                                    class="size-4 text-primary"
                                />
                            </div>
                            <p
                                class="text-xs leading-relaxed text-muted-foreground"
                            >
                                Perwakilan instansi luar, organisasi, atau
                                masyarakat umum.
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Input Nama -->
                <div>
                    <label
                        for="invitee-name"
                        class="mb-1.5 block text-sm font-semibold text-foreground"
                    >
                        Nama Lengkap <span class="text-destructive">*</span>
                    </label>
                    <Input
                        id="invitee-name"
                        v-model="name"
                        type="text"
                        placeholder="Contoh: Budi Santoso, S.Kom."
                        :disabled="processing"
                        :class="{ 'border-destructive': errors.name }"
                        @input="errors.name = ''"
                    />
                    <InputError :message="errors.name" class="mt-1" />
                </div>

                <!-- Input Email -->
                <div>
                    <label
                        for="invitee-email"
                        class="mb-1.5 block text-sm font-semibold text-foreground"
                    >
                        Alamat Email <span class="text-destructive">*</span>
                    </label>
                    <div class="relative">
                        <Input
                            id="invitee-email"
                            v-model="email"
                            type="email"
                            placeholder="nama@pemkot.go.id atau email pribadi"
                            :disabled="processing"
                            :class="{ 'border-destructive': errors.email }"
                            @input="errors.email = ''"
                        />
                        <Mail
                            class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                    </div>
                    <InputError :message="errors.email" class="mt-1" />
                </div>

                <!-- Informasi Keamanan & Ketentuan Aktivasi -->
                <div
                    class="space-y-2 rounded-xl border bg-muted/40 p-3.5 text-xs leading-relaxed text-muted-foreground"
                >
                    <div
                        class="flex items-center gap-2 font-medium text-foreground"
                    >
                        <Shield class="size-4 text-primary" />
                        <span>Ketentuan Keamanan Undangan:</span>
                    </div>
                    <ul class="list-disc space-y-1 pl-4">
                        <li>
                            Tautan aktivasi satu kali bersifat rahasia dan
                            berlaku selama <strong>72 jam</strong>.
                        </li>
                        <li>
                            Penerima akan menentukan kata sandinya sendiri saat
                            mengklik tautan aktivasi.
                        </li>
                        <li v-if="accountType === 'INTERNAL'">
                            Untuk akun internal, penetapan
                            <strong>Role</strong> dan
                            <strong>Jabatan</strong> dilakukan setelah aktivasi
                            melalui menu Struktur Organisasi & RBAC.
                        </li>
                        <li v-else>
                            Akun publik dapat langsung membuat pengajuan surat
                            masuk mandiri setelah aktivasi.
                        </li>
                    </ul>
                </div>

                <DialogFooter class="gap-2 pt-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        class="gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        <Send v-else class="size-4" />
                        <span>{{
                            processing ? 'Mengirim...' : 'Kirim Tautan Aktivasi'
                        }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
