<script setup lang="ts">
import { Building2, Plus } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    RegisterApprovalPayload,
    SenderOrganizationOption,
} from '@/types';

type SenderOrganizationSelection =
    RegisterApprovalPayload['sender_organization'];
type NewSenderOrganization = Extract<
    SenderOrganizationSelection,
    { mode: 'new' }
>;

const props = defineProps<{
    modelValue: RegisterApprovalPayload['sender_organization'];
    organizations: SenderOrganizationOption[];
    suggestedName: string;
    errors?: Record<string, string>;
}>();
const emit = defineEmits<{
    'update:modelValue': [
        value: RegisterApprovalPayload['sender_organization'],
    ];
}>();

const isExisting = computed(() => props.modelValue.mode === 'existing');

function useExisting(): void {
    emit('update:modelValue', {
        mode: 'existing',
        id: props.organizations[0]?.id ?? 0,
    });
}

function useNew(): void {
    emit('update:modelValue', {
        mode: 'new',
        name: props.suggestedName,
        address: null,
        contact: null,
    });
}

function updateNew(patch: Partial<NewSenderOrganization>): void {
    if (props.modelValue.mode !== 'new') {
        return;
    }

    emit('update:modelValue', { ...props.modelValue, ...patch });
}
</script>

<template>
    <fieldset class="space-y-3">
        <legend class="text-sm font-semibold">Instansi pengirim</legend>
        <div class="grid grid-cols-2 gap-2 rounded-xl bg-muted p-1">
            <Button
                type="button"
                :variant="isExisting ? 'secondary' : 'ghost'"
                class="min-h-10"
                :aria-pressed="isExisting"
                @click="useExisting"
            >
                <Building2 class="size-4" aria-hidden="true" />
                Pilih instansi
            </Button>
            <Button
                type="button"
                :variant="!isExisting ? 'secondary' : 'ghost'"
                class="min-h-10"
                :aria-pressed="!isExisting"
                @click="useNew"
            >
                <Plus class="size-4" aria-hidden="true" />
                Instansi baru
            </Button>
        </div>

        <div v-if="modelValue.mode === 'existing'" class="space-y-2">
            <Label for="sender-organization">Instansi aktif</Label>
            <select
                id="sender-organization"
                :value="modelValue.id"
                class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                @change="
                    emit('update:modelValue', {
                        mode: 'existing',
                        id: Number(($event.target as HTMLSelectElement).value),
                    })
                "
            >
                <option :value="0" disabled>Pilih instansi pengirim</option>
                <option
                    v-for="organization in organizations"
                    :key="organization.id"
                    :value="organization.id"
                >
                    {{ organization.name }}
                </option>
            </select>
            <InputError :message="errors?.['sender_organization.id']" />
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2">
            <label class="space-y-2 sm:col-span-2">
                <span class="text-sm font-medium">Nama instansi</span>
                <Input
                    :model-value="modelValue.name"
                    maxlength="200"
                    @update:model-value="updateNew({ name: String($event) })"
                />
                <InputError :message="errors?.['sender_organization.name']" />
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Alamat (opsional)</span>
                <Input
                    :model-value="modelValue.address ?? ''"
                    @update:model-value="
                        updateNew({ address: String($event) || null })
                    "
                />
                <InputError
                    :message="errors?.['sender_organization.address']"
                />
            </label>
            <label class="space-y-2">
                <span class="text-sm font-medium">Kontak (opsional)</span>
                <Input
                    :model-value="modelValue.contact ?? ''"
                    maxlength="150"
                    @update:model-value="
                        updateNew({ contact: String($event) || null })
                    "
                />
                <InputError
                    :message="errors?.['sender_organization.contact']"
                />
            </label>
        </div>
    </fieldset>
</template>
