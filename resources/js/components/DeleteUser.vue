<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { AlertOctagon, Trash2 } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center gap-3 pb-2">
            <div
                class="flex size-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300"
            >
                <AlertOctagon class="size-4.5" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-rose-600 dark:text-rose-400">
                    Zona Berbahaya
                </h3>
                <p class="text-xs text-muted-foreground">
                    Tindakan permanen terkait penghapusan data akun Anda
                </p>
            </div>
        </div>

        <div
            class="relative overflow-hidden rounded-2xl border border-rose-200/80 bg-gradient-to-br from-rose-50/50 via-white to-red-50/30 p-5 shadow-xs sm:p-6 dark:border-rose-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-rose-950/20"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h4 class="text-sm font-semibold text-rose-900 dark:text-rose-200">
                        Hapus Akun Pengguna
                    </h4>
                    <p class="max-w-lg text-xs leading-relaxed text-rose-700/90 dark:text-rose-300/80">
                        Setelah akun Anda dihapus, semua data dan riwayat terkait akan
                        dihapus secara permanen. Harap berhati-hati sebelum melanjutkan.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                            class="gap-2 shrink-0 bg-rose-600 font-medium text-white shadow-xs hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500"
                        >
                            <Trash2 class="size-4" />
                            Hapus Akun
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            @error="() => passwordInput?.focus()"
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-5"
                            v-slot="{ errors, processing, reset, clearErrors }"
                        >
                            <DialogHeader class="space-y-2 text-left">
                                <div class="flex size-11 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-300">
                                    <AlertOctagon class="size-6" />
                                </div>
                                <DialogTitle class="text-lg font-bold">
                                    Apakah Anda yakin ingin menghapus akun?
                                </DialogTitle>
                                <DialogDescription class="text-xs leading-relaxed text-muted-foreground">
                                    Tindakan ini tidak dapat dibatalkan. Masukkan kata sandi akun Anda untuk
                                    mengonfirmasi penghapusan permanen.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-2">
                                <Label for="password" class="text-xs font-semibold text-foreground">
                                    Konfirmasi Kata Sandi
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    ref="passwordInput"
                                    placeholder="Masukkan kata sandi akun Anda"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <DialogFooter class="gap-2 pt-2 sm:justify-end">
                                <DialogClose as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        Batal
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    data-test="confirm-delete-user-button"
                                    class="bg-rose-600 text-white hover:bg-rose-700"
                                >
                                    Ya, Hapus Akun Permanen
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>
</template>
