<script setup lang="ts">
import { AlertCircle, CheckCircle, UserCheck } from '@lucide/vue';
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
import type { UserManagementItem } from '@/types/user-management';

defineProps<{
    open: boolean;
    user: UserManagementItem | null;
    processing?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"
                    >
                        <UserCheck class="size-5" />
                    </span>
                    <div>
                        <DialogTitle class="text-xl font-bold tracking-tight">
                            Aktifkan Kembali Akun
                        </DialogTitle>
                        <DialogDescription
                            class="text-sm text-muted-foreground"
                        >
                            Pulihkan akses login pengguna ke dalam sistem
                            persuratan.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div v-if="user" class="space-y-4 py-2">
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
                        class="rounded-lg bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                    >
                        {{
                            user.account_type === 'INTERNAL'
                                ? 'Akun Internal'
                                : 'Akun Publik'
                        }}
                    </span>
                </div>

                <div
                    class="space-y-1.5 rounded-xl border border-blue-500/20 bg-blue-50/60 p-3.5 text-xs leading-relaxed text-blue-900 dark:bg-blue-950/20 dark:text-blue-200"
                >
                    <div
                        class="flex items-center gap-2 font-semibold text-blue-800 dark:text-blue-300"
                    >
                        <AlertCircle class="size-4" />
                        <span>Ketentuan Pemulihan Akun:</span>
                    </div>
                    <ul class="list-disc space-y-1 pl-4">
                        <li>
                            Pengguna dapat kembali login menggunakan kredensial
                            yang tersimpan sebelumnya.
                        </li>
                        <li v-if="user.account_type === 'INTERNAL'">
                            Pengaktifan kembali
                            <strong
                                >tidak otomatis mengembalikan role operasional
                                atau jabatan lama</strong
                            >. Hak akses harus ditetapkan ulang melalui menu
                            Organisasi & Role bila diperlukan.
                        </li>
                    </ul>
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
                    class="gap-2 bg-emerald-600 text-white hover:bg-emerald-700"
                    :disabled="processing"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    <CheckCircle v-else class="size-4" />
                    <span>{{
                        processing ? 'Memproses...' : 'Ya, Aktifkan Akun'
                    }}</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
