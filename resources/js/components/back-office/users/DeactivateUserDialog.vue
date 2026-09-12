<script setup lang="ts">
import { AlertOctagon, AlertTriangle, Ban, UserX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type {
    DeactivateUserPayload,
    UserManagementItem,
} from '@/types/user-management';

const props = defineProps<{
    open: boolean;
    user: UserManagementItem | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [payload: DeactivateUserPayload];
}>();

const reason = ref('');
const error = ref('');

const minLength = 10;
const maxLength = 1000;

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            reason.value = '';
            error.value = '';
        }
    },
);

const isReasonValid = computed(() => {
    const trimmed = reason.value.trim();

    return trimmed.length >= minLength && trimmed.length <= maxLength;
});

const isBlocked = computed(() => {
    return props.user ? !props.user.can_deactivate : true;
});

function handleConfirm(): void {
    if (props.processing || isBlocked.value) {
        return;
    }

    const trimmed = reason.value.trim();

    if (trimmed.length < minLength) {
        error.value = `Alasan penonaktifan minimal ${minLength} karakter.`;

        return;
    }

    if (trimmed.length > maxLength) {
        error.value = `Alasan penonaktifan maksimal ${maxLength} karakter.`;

        return;
    }

    emit('confirm', { reason: trimmed });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] max-w-xl overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive"
                    >
                        <UserX class="size-5" />
                    </span>
                    <div>
                        <DialogTitle
                            class="text-xl font-bold tracking-tight text-destructive"
                        >
                            Nonaktifkan Akun Pengguna
                        </DialogTitle>
                        <DialogDescription
                            class="text-sm text-muted-foreground"
                        >
                            Tindakan ini akan menghentikan akses login pengguna
                            ke dalam sistem.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div v-if="user" class="space-y-4 py-2">
                <!-- Info Ringkas Akun -->
                <div
                    class="flex items-center justify-between rounded-xl border bg-muted/30 p-3.5 text-sm"
                >
                    <div>
                        <p class="font-bold text-foreground">{{ user.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                    <span
                        class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                        :class="
                            user.account_type === 'INTERNAL'
                                ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                : 'bg-slate-500/10 text-slate-700 dark:text-slate-300'
                        "
                    >
                        {{
                            user.account_type === 'INTERNAL'
                                ? 'Akun Internal'
                                : 'Akun Publik'
                        }}
                    </span>
                </div>

                <!-- Guardrail Alert Jika Ditolak (409 Conflict / Invariant Protection) -->
                <Alert
                    v-if="isBlocked"
                    variant="destructive"
                    class="border-destructive/30 bg-destructive/5 text-destructive"
                >
                    <AlertOctagon class="size-4" />
                    <AlertTitle class="font-bold"
                        >Penonaktifan Ditolak Oleh Sistem (HTTP 409)</AlertTitle
                    >
                    <AlertDescription class="mt-1 text-xs leading-relaxed">
                        {{ user.deactivation_block_reason }}
                    </AlertDescription>
                </Alert>

                <!-- Peringatan Konsekuensi -->
                <div
                    v-else
                    class="space-y-1.5 rounded-xl border border-amber-500/30 bg-amber-50/70 p-3.5 text-xs text-amber-900 dark:bg-amber-950/20 dark:text-amber-200"
                >
                    <div class="flex items-center gap-2 font-semibold">
                        <AlertTriangle
                            class="size-4 text-amber-600 dark:text-amber-400"
                        />
                        <span>Dampak Penonaktifan:</span>
                    </div>
                    <ul class="list-disc space-y-1 pl-4 leading-relaxed">
                        <li>
                            Seluruh
                            <strong
                                >{{ user.active_sessions_count }} sesi
                                aktif</strong
                            >
                            dan remember token akan segera dicabut.
                        </li>
                        <li>
                            Role operasional akan dilepas sementara dari akun
                            pengguna.
                        </li>
                        <li>
                            Riwayat surat, aktivitas audit, dan catatan masa
                            lalu tetap tersimpan secara permanen.
                        </li>
                    </ul>
                </div>

                <!-- Input Alasan Penonaktifan -->
                <div>
                    <label
                        for="deactivation-reason"
                        class="mb-1.5 block text-sm font-semibold text-foreground"
                    >
                        Alasan Penonaktifan
                        <span class="text-destructive">*</span>
                    </label>
                    <p class="mb-2 text-xs text-muted-foreground">
                        Wajib dicatat untuk keperluan audit trail (minimal 10,
                        maksimal 1.000 karakter).
                    </p>
                    <textarea
                        id="deactivation-reason"
                        v-model="reason"
                        rows="4"
                        placeholder="Contoh: Pegawai bersangkutan telah purna tugas / menyelesaikan masa kerja..."
                        :disabled="processing || isBlocked"
                        class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-relaxed outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                        :class="{ 'border-destructive': error }"
                        @input="error = ''"
                    />
                    <div
                        class="mt-1 flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <InputError :message="error" />
                        <span
                            :class="{
                                'font-semibold text-destructive':
                                    reason.length > maxLength,
                            }"
                        >
                            {{ reason.length }}/{{ maxLength }} karakter
                        </span>
                    </div>
                </div>
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
                    type="button"
                    variant="destructive"
                    class="gap-2"
                    :disabled="processing || isBlocked || !isReasonValid"
                    @click="handleConfirm"
                >
                    <Spinner v-if="processing" />
                    <Ban v-else class="size-4" />
                    <span>{{
                        processing
                            ? 'Menonaktifkan...'
                            : 'Konfirmasi Nonaktifkan'
                    }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
