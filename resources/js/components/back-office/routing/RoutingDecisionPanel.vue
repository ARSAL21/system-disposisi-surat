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
import type { InitialRouteOption, InitialRoutePath } from '@/types';

const props = defineProps<{
    options: InitialRouteOption[];
    canRoute: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    confirm: [routePath: InitialRoutePath];
}>();

const selectedRoutePath = ref<InitialRoutePath | null>(null);
const confirmationOpen = ref(false);
const availableOptions = computed(() =>
    props.options.filter((option) => option.is_available),
);
const selectedOption = computed(
    () =>
        props.options.find(
            (option) => option.path === selectedRoutePath.value,
        ) ?? null,
);

watch(
    () => props.options,
    (options) => {
        if (
            selectedRoutePath.value !== null &&
            !options.some(
                (option) =>
                    option.path === selectedRoutePath.value &&
                    option.is_available,
            )
        ) {
            selectedRoutePath.value = null;
        }
    },
);

function updateTarget(value: unknown): void {
    selectedRoutePath.value =
        value === 'DIRECT_TO_SEKDA' || value === 'VIA_MAYOR' ? value : null;
}

function openConfirmation(): void {
    if (selectedOption.value && props.canRoute) {
        confirmationOpen.value = true;
    }
}

function confirmRouting(): void {
    if (!selectedOption.value || props.processing) {
        return;
    }

    emit('confirm', selectedOption.value.path);
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
                    <CardTitle>Tentukan jalur pimpinan</CardTitle>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Pilih jalur surat. Sistem selalu menentukan jabatan
                        tujuan resmi dari jalur tersebut.
                    </p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="grid gap-5 p-5 sm:p-6">
            <Alert v-if="!canRoute">
                <Info class="size-4" aria-hidden="true" />
                <AlertTitle>Akses baca-saja</AlertTitle>
                <AlertDescription
                    >Hanya Kepala Bagian Umum yang dapat mengirim routing
                    awal.</AlertDescription
                >
            </Alert>

            <Alert
                v-else-if="availableOptions.length === 0"
                variant="destructive"
            >
                <ShieldAlert class="size-4" aria-hidden="true" />
                <AlertTitle>Jalur belum tersedia</AlertTitle>
                <AlertDescription
                    >Periksa penugasan aktif Wali Kota dan Sekda sebelum
                    melakukan routing.</AlertDescription
                >
            </Alert>

            <div class="space-y-2">
                <Label for="routing-path"
                    >Jalur routing <span aria-hidden="true">*</span></Label
                >
                <Select
                    :model-value="selectedRoutePath ?? undefined"
                    :disabled="!canRoute || processing"
                    @update:model-value="updateTarget"
                >
                    <SelectTrigger
                        id="routing-path"
                        class="min-h-11 w-full"
                        :aria-invalid="Boolean(errors?.route_path)"
                    >
                        <SelectValue placeholder="Pilih jalur surat" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in options"
                            :key="option.path"
                            :value="option.path"
                            :disabled="!option.is_available"
                        >
                            {{ option.label }}
                            <template
                                v-if="option.target_position?.holder_name"
                            >
                                ·
                                {{
                                    option.target_position.holder_name
                                }}</template
                            >
                            <template v-else> · Jabatan kosong</template>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-xs leading-5 text-muted-foreground">
                    Wali Kota hanya memberi arahan formal kepada Sekda;
                    disposisi kepada Asisten selalu dibuat oleh Sekda.
                </p>
                <InputError :message="errors?.route_path" />
            </div>

            <div
                v-if="selectedOption"
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/65 p-4 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">
                        Jalur yang dipilih
                    </p>
                    <p class="mt-1 font-semibold">{{ selectedOption.label }}</p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ selectedOption.description }}
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
                    Setelah dikirim, status surat menjadi
                    <strong class="text-foreground">ROUTED</strong>. Tujuan dan
                    jalur tidak dapat diubah atau dihapus.
                </p>
            </div>

            <Button
                type="button"
                class="min-h-11 w-full"
                :disabled="
                    !canRoute ||
                    !selectedOption ||
                    processing ||
                    availableOptions.length === 0
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
                <DialogTitle>Kirim routing surat?</DialogTitle>
                <DialogDescription class="leading-6"
                    >Periksa kembali jalur sebelum menyimpan routing
                    permanen.</DialogDescription
                >
            </DialogHeader>

            <div
                v-if="selectedOption"
                class="flex items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-sm font-semibold">
                        {{ selectedOption.label }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ selectedOption.description }}
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="processing"
                    @click="confirmationOpen = false"
                    >Periksa kembali</Button
                >
                <Button
                    type="button"
                    class="min-h-11"
                    :disabled="processing || !selectedOption"
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
