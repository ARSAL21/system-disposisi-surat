<script setup lang="ts">
import {
    Check,
    Crown,
    Info,
    Route as RouteIcon,
    Send,
    ShieldAlert,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { ExecutivePositionOption } from '@/types';

const props = defineProps<{
    positions: ExecutivePositionOption[];
    canRoute: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    confirm: [targetPositionId: number];
}>();

const selectedPositionId = ref<number | null>(null);
const confirmationOpen = ref(false);
const availablePositions = computed(() =>
    props.positions.filter((position) => position.is_available),
);
const selectedPosition = computed(
    () =>
        props.positions.find(
            (position) => position.id === selectedPositionId.value,
        ) ?? null,
);

watch(
    () => props.positions,
    (positions) => {
        if (
            selectedPositionId.value !== null &&
            !positions.some(
                (position) =>
                    position.id === selectedPositionId.value &&
                    position.is_available,
            )
        ) {
            selectedPositionId.value = null;
        }
    },
);

function updateTarget(value: unknown): void {
    const targetId = Number(value);
    selectedPositionId.value = Number.isInteger(targetId) ? targetId : null;
}

function openConfirmation(): void {
    if (!selectedPosition.value || !props.canRoute) {
        return;
    }

    confirmationOpen.value = true;
}

function confirmRouting(): void {
    if (!selectedPosition.value || props.processing) {
        return;
    }

    emit('confirm', selectedPosition.value.id);
}
</script>

<template>
    <Card class="border-blue-200/80 py-0 shadow-sm dark:border-blue-950">
        <CardHeader class="border-b p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                >
                    <RouteIcon class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <CardTitle>Tentukan tujuan pimpinan</CardTitle>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Pilih tepat satu jabatan eksekutif yang sedang memiliki
                        pejabat aktif.
                    </p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="grid gap-5 p-5 sm:p-6">
            <Alert v-if="!canRoute">
                <Info class="size-4" aria-hidden="true" />
                <AlertTitle>Akses baca-saja</AlertTitle>
                <AlertDescription>
                    Anda dapat memeriksa surat, tetapi hanya Kepala Bagian Umum
                    yang dapat mengirim routing awal.
                </AlertDescription>
            </Alert>

            <Alert
                v-else-if="availablePositions.length === 0"
                variant="destructive"
            >
                <ShieldAlert class="size-4" aria-hidden="true" />
                <AlertTitle>Tidak ada tujuan yang dapat dipilih</AlertTitle>
                <AlertDescription>
                    Wali Kota dan Sekretaris Daerah belum memiliki Position
                    Assignment aktif. Routing harus menunggu penugasan jabatan.
                </AlertDescription>
            </Alert>

            <div class="space-y-2">
                <Label for="routing-target">
                    Tujuan routing <span aria-hidden="true">*</span>
                </Label>
                <Select
                    :model-value="
                        selectedPositionId === null
                            ? undefined
                            : String(selectedPositionId)
                    "
                    :disabled="!canRoute || processing"
                    @update:model-value="updateTarget"
                >
                    <SelectTrigger
                        id="routing-target"
                        class="min-h-11 w-full"
                        :aria-invalid="Boolean(errors?.target_position_id)"
                    >
                        <SelectValue
                            placeholder="Pilih Wali Kota atau Sekretaris Daerah"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="position in positions"
                            :key="position.id"
                            :value="String(position.id)"
                            :disabled="!position.is_available"
                        >
                            {{ position.name }}
                            <template v-if="position.holder_name">
                                · {{ position.holder_name }}
                            </template>
                            <template v-else> · Jabatan kosong</template>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs leading-5 text-muted-foreground">
                    Pilihan didasarkan pada Position Assignment aktif, bukan
                    Role pengguna.
                </p>
                <InputError :message="errors?.target_position_id" />
            </div>

            <div
                v-if="selectedPosition"
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/65 p-4 dark:border-violet-900 dark:bg-violet-950/25"
                aria-live="polite"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">
                        Pimpinan yang akan menerima
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ selectedPosition.name }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ selectedPosition.holder_name }}
                    </p>
                </div>
            </div>

            <div
                class="flex items-start gap-3 rounded-2xl bg-muted/55 p-4 text-sm"
            >
                <ShieldAlert
                    class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-muted-foreground">
                    Setelah dikirim, status surat berubah menjadi
                    <strong class="text-foreground">ROUTED</strong> dan dokumen
                    tidak dapat dikoreksi. M5 tidak menyediakan ubah tujuan,
                    tarik kembali, atau hapus routing.
                </p>
            </div>

            <Button
                type="button"
                class="min-h-11 w-full"
                :disabled="
                    !canRoute ||
                    !selectedPosition ||
                    processing ||
                    availablePositions.length === 0
                "
                @click="openConfirmation"
            >
                <Send class="size-4" aria-hidden="true" />
                Lanjutkan ke konfirmasi
            </Button>
        </CardContent>
    </Card>

    <Dialog
        :open="confirmationOpen"
        @update:open="!processing ? (confirmationOpen = $event) : undefined"
    >
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Kirim routing kepada pimpinan?</DialogTitle>
                <DialogDescription class="leading-6">
                    Periksa kembali tujuan sebelum menyimpan route permanen.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="selectedPosition"
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-sm font-semibold">
                        {{ selectedPosition.name }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ selectedPosition.holder_name }}
                    </p>
                </div>
            </div>

            <div
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/75 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/25 dark:text-amber-100"
            >
                <ShieldAlert
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <p class="leading-6">
                    Tindakan ini tidak dapat dibatalkan. Pimpinan terpilih akan
                    menerima surat pada inbox dan menunggu disposisi pertama.
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="processing"
                    @click="confirmationOpen = false"
                >
                    Periksa kembali
                </Button>
                <Button
                    type="button"
                    class="min-h-11"
                    :disabled="processing || !selectedPosition"
                    @click="confirmRouting"
                >
                    <Spinner v-if="processing" />
                    <Check v-else class="size-4" aria-hidden="true" />
                    {{
                        processing ? 'Mengirim routing...' : 'Ya, kirim routing'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
